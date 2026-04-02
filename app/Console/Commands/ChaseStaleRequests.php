<?php

namespace App\Console\Commands;

use App\Mail\RequestChase;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestNote;
use App\Models\EmailLog;
use App\Models\Setting;
use Illuminate\Console\Command;

class ChaseStaleRequests extends Command
{
    protected $signature = 'requests:chase';
    protected $description = 'Send chase reminders for stale change requests that have not moved status';

    public function handle(): int
    {
        if (!Setting::get('chase_enabled')) {
            $this->info('Chase reminders are disabled.');
            return 0;
        }

        $chaseHours = (int) Setting::get('chase_hours', 48);
        $unassignedEmail = Setting::get('chase_unassigned_email');

        $cutoff = now()->subHours($chaseHours);

        $staleRequests = ChangeRequest::with(['assignee', 'site'])
            ->whereNotIn('status', ChangeRequest::TERMINAL_STATUSES)
            ->where('updated_at', '<', $cutoff)
            ->get();

        $sent = 0;

        foreach ($staleRequests as $request) {
            $recipient = null;

            if ($request->assigned_to && $request->assignee) {
                $recipient = $request->assignee->email;
            } elseif ($unassignedEmail) {
                $recipient = $unassignedEmail;
            }

            if (!$recipient) {
                continue;
            }

            EmailLog::dispatch($recipient, new RequestChase($request), $request);

            // Log a note on the request
            ChangeRequestNote::create([
                'change_request_id' => $request->id,
                'user_id' => null,
                'note' => 'Automated chase reminder sent',
            ]);

            // Touch updated_at so it doesn't chase again immediately
            $request->touch();

            $sent++;
        }

        $this->info("Sent {$sent} reminders for stale requests.");

        return 0;
    }
}
