<?php

namespace App\Console\Commands;

use App\Mail\ScheduledForActionToday;
use App\Models\ChangeRequest;
use App\Models\EmailLog;
use Illuminate\Console\Command;

class NotifyScheduledForToday extends Command
{
    protected $signature = 'requests:notify-scheduled-today';
    protected $description = 'Email the assignee for each request scheduled to be actioned today';

    public function handle(): int
    {
        $requests = ChangeRequest::with(['assignee', 'site'])
            ->where('status', 'scheduled')
            ->whereDate('scheduled_date', today())
            ->get();

        $sent = 0;

        foreach ($requests as $request) {
            // Only notify when there's someone to act on it.
            if (!$request->assigned_to || !$request->assignee) {
                continue;
            }

            // Don't send twice if the task runs more than once in a day.
            $alreadySent = EmailLog::where('change_request_id', $request->id)
                ->where('mailable_class', 'ScheduledForActionToday')
                ->whereDate('created_at', today())
                ->exists();

            if ($alreadySent) {
                continue;
            }

            // The EmailLog row records the send and surfaces in the request's
            // activity trail, so no separate note is needed.
            EmailLog::dispatch($request->assignee->email, new ScheduledForActionToday($request), $request);

            $sent++;
        }

        $this->info("Sent {$sent} scheduled-for-today reminders.");

        return 0;
    }
}
