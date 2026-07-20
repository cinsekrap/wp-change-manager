<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ChangeRequest extends Model
{
    protected $fillable = [
        'reference', 'request_type', 'site_id', 'page_url', 'page_title', 'cpt_slug',
        'is_new_page', 'status', 'previous_status', 'priority', 'rejection_reason', 'hold_reason',
        'clarification_message', 'clarification_requested_at',
        'sla_paused_at', 'sla_paused_hours', 'requester_name', 'requester_email',
        'requester_phone', 'requester_role', 'check_answers',
        'access_recipient_name', 'access_recipient_email',
        'training_token', 'training_sent_at', 'training_confirmed_at',
        'deadline_date', 'deadline_reason', 'scheduled_date', 'assigned_to',
        'approval_overridden', 'approval_overridden_by', 'approval_overridden_at',
    ];

    protected function casts(): array
    {
        return [
            'is_new_page' => 'boolean',
            'check_answers' => 'array',
            'deadline_date' => 'date',
            'scheduled_date' => 'date',
            'training_sent_at' => 'datetime',
            'training_confirmed_at' => 'datetime',
            'sla_paused_at' => 'datetime',
            'clarification_requested_at' => 'datetime',
            'approval_overridden' => 'boolean',
            'approval_overridden_at' => 'datetime',
        ];
    }

    public const STATUSES = ['requested', 'requires_referral', 'referred', 'approved', 'training', 'trained', 'scheduled', 'on_hold', 'awaiting_user', 'done', 'declined', 'cancelled'];

    public const POST_REFERRED_STATUSES = ['approved', 'training', 'trained', 'scheduled', 'done'];

    public const TERMINAL_STATUSES = ['done', 'declined', 'cancelled'];

    public const ACCESS_ONLY_STATUSES = ['training', 'trained'];

    public const CHANGE_ONLY_STATUSES = ['scheduled'];

    /**
     * Statuses where the ball is out of the team's court entirely, so the SLA
     * clock pauses and the deadline is pushed back by the time spent in them.
     */
    public const SLA_PAUSED_STATUSES = ['on_hold', 'awaiting_user'];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    protected static function booted(): void
    {
        // Keep SLA pause bookkeeping in one place so every transition path
        // (status form, bulk actions, public responses) behaves the same.
        static::updating(function (self $changeRequest) {
            if (!$changeRequest->isDirty('status')) {
                return;
            }

            $old = $changeRequest->getOriginal('status');
            $new = $changeRequest->status;
            $wasPaused = in_array($old, self::SLA_PAUSED_STATUSES);
            $isPaused = in_array($new, self::SLA_PAUSED_STATUSES);

            if (!$wasPaused && $isPaused) {
                $changeRequest->sla_paused_at = now();
                $changeRequest->previous_status = $old;
            } elseif ($wasPaused && !$isPaused) {
                if ($changeRequest->sla_paused_at) {
                    $changeRequest->sla_paused_hours += $changeRequest->businessHoursBetween($changeRequest->sla_paused_at, now());
                }
                $changeRequest->sla_paused_at = null;
                $changeRequest->previous_status = null;
            }

            if ($old === 'on_hold' && $new !== 'on_hold') {
                $changeRequest->hold_reason = null;
            }

            if ($old === 'awaiting_user' && $new !== 'awaiting_user') {
                $changeRequest->clarification_message = null;
                $changeRequest->clarification_requested_at = null;
            }
        });
    }

    public static function generateReference(): string
    {
        return DB::transaction(function () {
            $today = now()->format('Ymd');
            $prefix = "WCR-{$today}-";

            $count = static::where('reference', 'like', "{$prefix}%")->lockForUpdate()->count();

            return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        });
    }

    public static function generateTrainingToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function isAccessRequest(): bool
    {
        return $this->request_type === 'access';
    }

    /**
     * Statuses applicable to this request's type: access requests can't be
     * scheduled, change requests don't go through training.
     */
    public function statusOptions(): array
    {
        $excluded = $this->isAccessRequest() ? self::CHANGE_ONLY_STATUSES : self::ACCESS_ONLY_STATUSES;

        return array_values(array_diff(self::STATUSES, $excluded));
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function cptType()
    {
        return $this->belongsTo(CptType::class, 'cpt_slug', 'slug');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function items()
    {
        return $this->hasMany(ChangeRequestItem::class)->orderBy('sort_order');
    }

    public function notes()
    {
        return $this->hasMany(ChangeRequestNote::class)->orderBy('created_at');
    }

    public function statusLogs()
    {
        return $this->hasMany(ChangeRequestStatusLog::class)->orderBy('created_at');
    }

    public function approvers()
    {
        return $this->hasMany(ChangeRequestApprover::class)->orderBy('created_at');
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class)->orderBy('created_at');
    }

    public function approvalOverriddenByUser()
    {
        return $this->belongsTo(User::class, 'approval_overridden_by');
    }

    public function approvalsComplete(): bool
    {
        $approvers = $this->approvers;
        if ($approvers->isEmpty()) {
            return true;
        }

        // All ungrouped approvers must have responded
        if ($approvers->whereNull('group')->contains(fn($a) => $a->status === 'pending')) {
            return false;
        }

        // Each group must have at least one response
        foreach ($approvers->whereNotNull('group')->groupBy('group') as $members) {
            if ($members->every(fn($a) => $a->status === 'pending')) {
                return false;
            }
        }

        return true;
    }

    public function approvalsAllApproved(): bool
    {
        $approvers = $this->approvers;
        if ($approvers->isEmpty()) {
            return true;
        }

        // All ungrouped approvers must be approved
        if ($approvers->whereNull('group')->contains(fn($a) => $a->status !== 'approved')) {
            return false;
        }

        // Each group must have at least one approved member
        foreach ($approvers->whereNotNull('group')->groupBy('group') as $members) {
            if (!$members->contains(fn($a) => $a->status === 'approved')) {
                return false;
            }
        }

        return true;
    }

    public function canMovePastReferred(): bool
    {
        return $this->approval_overridden || $this->approvalsAllApproved();
    }

    public function hasPendingApprovers(): bool
    {
        $approvers = $this->approvers;

        if ($approvers->where('group', null)->where('status', 'pending')->isNotEmpty()) {
            return true;
        }

        foreach ($approvers->whereNotNull('group')->groupBy('group') as $members) {
            $hasPending = $members->contains(fn($a) => $a->status === 'pending');
            $hasResponse = $members->contains(fn($a) => $a->status !== 'pending');
            if ($hasPending && !$hasResponse) {
                return true;
            }
        }

        return false;
    }

    public function groupSatisfied(string $group): bool
    {
        return $this->approvers()->where('group', $group)->where('status', 'approved')->exists();
    }

    public function pendingGroupMembers(string $group)
    {
        return $this->approvers()->where('group', $group)->where('status', 'pending')->get();
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ---- SLA helpers ----

    /**
     * Return the SLA hours for this request's priority.
     * Checks admin-configured settings first, then falls back to config/sla.php.
     */
    public function slaHours(): int
    {
        $priority = $this->priority ?: 'normal';
        $settingValue = Setting::get("sla_{$priority}");

        if ($settingValue !== null && $settingValue !== '') {
            return (int) $settingValue;
        }

        return (int) config("sla.{$priority}", 40);
    }

    /**
     * Calculate the SLA deadline by adding business hours (Mon-Fri, 8h/day) to created_at.
     * Time spent in an SLA-paused status (accumulated in sla_paused_hours)
     * extends the deadline by the same amount.
     */
    public function slaDeadline(): Carbon
    {
        $hours = $this->slaHours() + (int) $this->sla_paused_hours;
        $fullDays = intdiv($hours, 8);
        $remainingHours = $hours % 8;

        $date = $this->created_at->copy()->startOfDay();

        // Add full business days
        $added = 0;
        while ($added < $fullDays) {
            $date->addDay();
            if ($date->isWeekday()) {
                $added++;
            }
        }

        // Add remaining hours
        if ($remainingHours > 0) {
            $date->addDay();
            while (!$date->isWeekday()) {
                $date->addDay();
            }
            // Set to 9am + remaining hours
            $date->setTime(9 + $remainingHours, 0, 0);
        } else {
            // End of the last business day (17:00)
            $date->setTime(17, 0, 0);
        }

        return $date;
    }

    /**
     * Whether the SLA clock has been stopped. Scheduling a request to an
     * agreed date counts as meeting the SLA, so it no longer accrues time.
     * Likewise while awaiting training confirmation — the ball is with the
     * access recipient, not the team — and while in an SLA-paused status.
     */
    public function slaStopped(): bool
    {
        return in_array($this->status, array_merge(['scheduled', 'training'], self::SLA_PAUSED_STATUSES));
    }

    /**
     * Check if this request is overdue (past SLA deadline).
     */
    public function isOverSla(): bool
    {
        if ($this->slaStopped()) {
            return false;
        }

        return now()->greaterThan($this->slaDeadline());
    }

    /**
     * Get the SLA status: 'on_track', 'at_risk', or 'overdue'.
     */
    public function slaStatus(): string
    {
        $deadline = $this->slaDeadline();
        $now = now();

        if ($now->greaterThan($deadline)) {
            return 'overdue';
        }

        // At risk = within 20% of the total SLA time remaining
        $totalSeconds = $this->created_at->diffInSeconds($deadline);
        $remainingSeconds = $now->diffInSeconds($deadline);
        $threshold = $totalSeconds * 0.20;

        if ($remainingSeconds <= $threshold) {
            return 'at_risk';
        }

        return 'on_track';
    }

    /**
     * Get the remaining or overdue business hours for SLA display.
     * Returns positive for remaining, negative for overdue.
     */
    public function slaRemainingHours(): int
    {
        $deadline = $this->slaDeadline();
        $now = now();

        if ($now->greaterThan($deadline)) {
            // Count business hours overdue
            return -$this->countBusinessHours($deadline, $now);
        }

        return $this->countBusinessHours($now, $deadline);
    }

    /**
     * Count approximate business hours between two dates.
     * Uses simple weekday counting x 8 hours per day.
     */
    private function countBusinessHours(Carbon $from, Carbon $to): int
    {
        $days = 0;
        $current = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($current->lt($end)) {
            $current->addDay();
            if ($current->isWeekday()) {
                $days++;
            }
        }

        return max($days * 8, 1);
    }

    /**
     * Business hours between two dates without the display floor of 1 —
     * a pause that starts and ends the same day adds nothing to the SLA.
     */
    public function businessHoursBetween(Carbon $from, Carbon $to): int
    {
        $days = 0;
        $current = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($current->lt($end)) {
            $current->addDay();
            if ($current->isWeekday()) {
                $days++;
            }
        }

        return $days * 8;
    }

    /**
     * Whether this request is in an active (non-terminal) status.
     */
    public function isActive(): bool
    {
        return !in_array($this->status, self::TERMINAL_STATUSES);
    }
}
