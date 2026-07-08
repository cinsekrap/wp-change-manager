<?php

namespace App\Console\Commands;

use App\Models\ChangeRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurgeCompletedUploads extends Command
{
    protected $signature = 'uploads:purge-completed {--days=30 : Purge attachments for requests closed at least this many days ago}';

    protected $description = 'Delete attachment files for requests that have been done, declined, or cancelled for the given number of days (file records are kept)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->subDays($days);
        $disk = Storage::disk('local');
        $purgedFiles = 0;
        $purgedRequests = 0;

        $candidates = ChangeRequest::whereIn('status', ChangeRequest::TERMINAL_STATUSES)
            ->whereHas('items.files', fn ($query) => $query->whereNull('purged_at'))
            ->with(['items.files' => fn ($query) => $query->whereNull('purged_at')])
            ->get();

        foreach ($candidates as $request) {
            // When the request entered its current (terminal) status; a request
            // reopened and re-closed gets a fresh 30 days from the latest change.
            $closedAt = $request->statusLogs()->max('created_at');
            $closedAt = $closedAt ? Carbon::parse($closedAt) : $request->updated_at;

            if ($closedAt->isAfter($threshold)) {
                continue;
            }

            foreach ($request->items as $item) {
                foreach ($item->files as $file) {
                    if ($disk->exists($file->stored_path)) {
                        $disk->delete($file->stored_path);
                    }

                    $file->update(['purged_at' => now()]);
                    $purgedFiles++;
                }
            }

            $directory = "uploads/{$request->reference}";
            if ($disk->exists($directory) && empty($disk->allFiles($directory))) {
                $disk->deleteDirectory($directory);
            }

            $purgedRequests++;
        }

        $message = "Purged {$purgedFiles} attachment(s) from {$purgedRequests} request(s) closed over {$days} day(s) ago.";
        $this->info($message);
        Log::info("uploads:purge-completed - {$message}");

        return self::SUCCESS;
    }
}
