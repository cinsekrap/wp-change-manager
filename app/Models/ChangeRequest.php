<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ChangeRequest extends Model
{
    protected $fillable = [
        'reference', 'site_id', 'page_url', 'page_title', 'cpt_slug',
        'is_new_page', 'status', 'priority', 'rejection_reason', 'requester_name', 'requester_email',
        'requester_phone', 'requester_role', 'check_answers',
        'deadline_date', 'deadline_reason', 'assigned_to',
        'approval_overridden', 'approval_overridden_by', 'approval_overridden_at',
    ];

    protected function casts(): array
    {
        return [
            'is_new_page' => 'boolean',
            'check_answers' => 'array',
            'deadline_date' => 'date',
            'approval_overridden' => 'boolean',
            'approval_overridden_at' => 'datetime',
        ];
    }

    public const STATUSES = ['requested', 'requires_referral', 'referred', 'approved', 'scheduled', 'done', 'declined', 'cancelled'];

    public const POST_REFERRED_STATUSES = ['approved', 'scheduled', 'done'];

    public const TERMINAL_STATUSES = ['done', 'declined', 'cancelled'];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    public static function generateReference(): string
    {
        return DB::transaction(function () {
            $today = now()->format('Ymd');
            $prefix = "WCR-{$today}-";

            $count = static::where('reference', 'like', "{$prefix}%")->lockForUpdate()->count();

            return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        });
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
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

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'change_request_tag')->withTimestamps();
    }

    public function approvalsComplete(): bool
    {
        $approvers = $this->approvers;
        if ($approvers->isEmpty()) {
            return true;
        }
        return $approvers->every(fn($a) => $a->status !== 'pending');
    }

    public function approvalsAllApproved(): bool
    {
        $approvers = $this->approvers;
        if ($approvers->isEmpty()) {
            return true;
        }
        return $approvers->every(fn($a) => $a->status === 'approved');
    }

    public function canMovePastReferred(): bool
    {
        return $this->approval_overridden || $this->approvalsAllApproved();
    }

    public function hasPendingApprovers(): bool
    {
        return $this->approvers()->where('status', 'pending')->exists();
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
     */
    public function slaDeadline(): Carbon
    {
        $hours = $this->slaHours();
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
     * Check if this request is overdue (past SLA deadline).
     */
    public function isOverSla(): bool
    {
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
     * Whether this request is in an active (non-terminal) status.
     */
    public function isActive(): bool
    {
        return !in_array($this->status, self::TERMINAL_STATUSES);
    }
}
