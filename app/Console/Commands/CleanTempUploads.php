<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanTempUploads extends Command
{
    protected $signature = 'uploads:clean {--hours=24 : Delete files older than this many hours}';

    protected $description = 'Delete orphaned temporary upload files older than the specified hours';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $directory = 'uploads/temp';
        $disk = Storage::disk('local');

        if (!$disk->exists($directory)) {
            $this->info('Temp upload directory does not exist. Nothing to clean.');
            return self::SUCCESS;
        }

        $files = $disk->files($directory);
        $threshold = now()->subHours($hours)->getTimestamp();
        $deleted = 0;

        foreach ($files as $file) {
            $fullPath = $disk->path($file);

            if (File::lastModified($fullPath) < $threshold) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $message = "Cleaned {$deleted} temporary upload file(s) older than {$hours} hour(s).";
        $this->info($message);
        Log::info($message);

        return self::SUCCESS;
    }
}
