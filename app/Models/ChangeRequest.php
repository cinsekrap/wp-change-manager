<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ChangeRequest extends Model
{
    protected $fillable = [
        'reference', 'request_type', 'site_id', 'page_url', 'page_title', 'cpt_slug',
        'content_type', 'content_brief', 'estimated_hours', 'public_title', 'draft_content', 'draft_fields',
        'published_url', 'published_title',
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
            'draft_fields' => 'array',
            'content_brief' => 'array',
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

    public const STATUSES = ['suggested', 'scoped', 'awaiting_funding', 'in_progress', 'awaiting_approval', 'requested', 'requires_referral', 'referred', 'approved', 'training', 'trained', 'scheduled', 'on_hold', 'awaiting_user', 'done', 'declined', 'cancelled'];

    public const POST_REFERRED_STATUSES = ['approved', 'training', 'trained', 'scheduled', 'done'];

    public const TERMINAL_STATUSES = ['done', 'declined', 'cancelled'];

    public const ACCESS_ONLY_STATUSES = ['training', 'trained'];

    public const CHANGE_ONLY_STATUSES = ['scheduled'];

    /**
     * Intake and referral, shared by the change and access lanes. Content has its
     * own equivalents — suggested for intake, awaiting_approval for sign-off, and
     * a named clinical approver rather than a site's — so it never passes through
     * any of these.
     */
    public const REFERRAL_STATUSES = ['requested', 'requires_referral', 'referred'];

    /**
     * The suggestion-and-funding half of the content lifecycle. Deliberately NOT in
     * SLA_PAUSED_STATUSES: content is slow by design, and the reported turnaround
     * should say so rather than flatter it.
     */
    public const CONTENT_ONLY_STATUSES = ['suggested', 'scoped', 'awaiting_funding', 'in_progress', 'awaiting_approval'];

    /**
     * Statuses where the ball is out of the team's court entirely, so the SLA
     * clock pauses and the deadline is pushed back by the time spent in them.
     */
    public const SLA_PAUSED_STATUSES = ['on_hold', 'awaiting_user'];

    /**
     * Display names for statuses whose slug doesn't read well on its own.
     * Anything not listed falls back to a humanised slug.
     */
    public const STATUS_LABELS = [
        'requires_referral' => 'Requires Referral',
        'training' => 'Awaiting Training',
        'trained' => 'Training Confirmed',
        'on_hold' => 'On Hold',
        'awaiting_user' => 'Awaiting User',
        'suggested' => 'Suggested',
        'scoped' => 'Sized Up',
        'awaiting_funding' => 'Awaiting Funding',
        'in_progress' => 'Being Written',
        'awaiting_approval' => 'Awaiting Clinical Approval',
    ];

    /**
     * Badge classes per status. Kept beside the labels so the two cannot drift —
     * they were previously copied into six views and had already diverged.
     * Must be literal strings: Tailwind's scanner cannot see a computed class.
     */
    public const STATUS_COLORS = [
        'requested' => 'bg-amber-100 text-amber-800',
        'requires_referral' => 'bg-pink-100 text-pink-800',
        'referred' => 'bg-orange-100 text-orange-800',
        'approved' => 'bg-hcrg-burgundy/20 text-hcrg-burgundy',
        'training' => 'bg-sky-100 text-sky-800',
        'trained' => 'bg-teal-100 text-teal-800',
        'scheduled' => 'bg-purple-100 text-purple-800',
        'on_hold' => 'bg-yellow-100 text-yellow-700',
        'awaiting_user' => 'bg-cyan-100 text-cyan-700',
        'done' => 'bg-emerald-100 text-emerald-800',
        'declined' => 'bg-red-100 text-red-800',
        'cancelled' => 'bg-gray-200 text-gray-600',
        'suggested' => 'bg-stone-100 text-stone-700',
        'scoped' => 'bg-stone-200 text-stone-700',
        'awaiting_funding' => 'bg-amber-100 text-amber-800',
        'in_progress' => 'bg-sky-100 text-sky-800',
        'awaiting_approval' => 'bg-fuchsia-100 text-fuchsia-800',
    ];

    public static function statusColor(string $status): string
    {
        return self::STATUS_COLORS[$status] ?? 'bg-gray-100 text-gray-800';
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    /**
     * The fingerprint clinical approval is bound to. An approval that does not
     * name the text it approved is not a defensible governance trail.
     */
    public function draftContentHash(): ?string
    {
        return $this->draft_content === null ? null : hash('sha256', $this->draft_content);
    }

    /**
     * Is there a live clinical sign-off against exactly this copy?
     *
     * When there is, the draft is locked: a sign-off is a clinician putting their
     * name to a specific text, and changing it should be a deliberate act rather
     * than something discovered after the fact.
     */
    public function hasBoundApproval(): bool
    {
        $hash = $this->draftContentHash();

        if ($hash === null) {
            return false;
        }

        return $this->approvers()
            ->where('status', 'approved')
            ->where('approved_content_hash', $hash)
            ->exists();
    }

    /**
     * Approvals that were given against a different version of the copy.
     */
    public function staleApprovals()
    {
        $hash = $this->draftContentHash();

        return $this->approvers()
            ->where('status', 'approved')
            ->whereNotNull('approved_content_hash')
            ->get()
            ->filter(fn ($a) => $a->approved_content_hash !== $hash);
    }

    protected static function booted(): void
    {
        // Clinical approval binds to a version of the copy. Editing an approved
        // draft voids the sign-off wherever the edit came from, rather than
        // leaving a record that reads "approved" for text nobody approved.
        static::updated(function (self $changeRequest) {
            if (!$changeRequest->wasChanged('draft_content') || !$changeRequest->isContentRequest()) {
                return;
            }

            $stale = $changeRequest->staleApprovals();
            if ($stale->isEmpty()) {
                return;
            }

            foreach ($stale as $approver) {
                $approver->update([
                    'status' => 'pending',
                    'responded_at' => null,
                    'approved_content_hash' => null,
                    'approved_content_snapshot' => null,
                    'token' => ChangeRequestApprover::generateToken(),
                ]);
            }

            if (in_array($changeRequest->status, self::POST_REFERRED_STATUSES)) {
                $changeRequest->updateQuietly(['status' => 'awaiting_approval']);
            }
        });

        // The wizard shows a typical wait built from completed content requests.
        // Without this it stays stale for up to six hours after each completion —
        // including the first three, when the figure first becomes publishable.
        static::updated(function (self $changeRequest) {
            if ($changeRequest->isContentRequest() && $changeRequest->wasChanged('status')) {
                Cache::forget('content_wait_days');
            }
        });

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
     * A human description of what this request is about, for emails and views.
     *
     * Content requests have no page: page_url is the placeholder 'new-content' and
     * page_title is null, which rendered as "New page:" followed by nothing in ten
     * email templates.
     */
    public function subjectDescription(): string
    {
        if ($this->isAccessRequest()) {
            return $this->cptType->name ?? $this->cpt_slug;
        }

        if ($this->isContentRequest()) {
            // page_title is the working title on content the team started itself;
            // without it there is only the generic type label to go on.
            return $this->public_title
                ?: $this->page_title
                ?: config("content-types.{$this->content_type}.label", 'New content');
        }

        return $this->page_title ?: $this->page_url;
    }

    /**
     * The page type this content will become, if its kind has one.
     */
    public function contentCptType(): ?CptType
    {
        if (! $this->isContentRequest() || ! $this->content_type) {
            return null;
        }

        return CptType::forContentKind($this->content_type);
    }

    /**
     * The fields this content is written into.
     *
     * Empty when the kind has no page type, or the page type has no fields
     * configured — in which case the copy is one box of text, as it was before.
     */
    public function contentFields(): array
    {
        return $this->contentCptType()?->form_config['content_areas'] ?? [];
    }

    public function hasStructuredDraft(): bool
    {
        return $this->contentFields() !== [];
    }

    /**
     * The fields rendered as plain text.
     *
     * Clinical approval binds to a hash of draft_content and the reading-age
     * check reads it, so structured copy is written down here as well. Field
     * names are included because a heading changing is a change to the copy.
     */
    public static function renderDraftFields(array $fields, array $values): string
    {
        $out = [];

        foreach ($fields as $field) {
            $name = $field['name'] ?? '';
            $entries = $values[$name] ?? null;

            foreach (self::normaliseFieldValue($entries) as $entry) {
                $body = is_array($entry)
                    ? collect($entry)->map(fn ($v, $k) => trim("{$k}: {$v}"))->filter()->implode("\n")
                    : trim((string) $entry);

                if ($body !== '') {
                    $out[] = trim($name."\n".$body);
                }
            }
        }

        return implode("\n\n", $out);
    }

    /** A field holds one value, or several when it repeats. */
    private static function normaliseFieldValue(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        // A repeatable field arrives as a list; a plain one as a string, and a
        // group of sub-fields as a single associative array.
        if (is_array($value) && array_is_list($value)) {
            return $value;
        }

        return [$value];
    }

    public function isContentRequest(): bool
    {
        return $this->request_type === 'content';
    }

    /**
     * Statuses applicable to this request's type: access requests can't be
     * scheduled, change requests don't go through training.
     */
    public function statusOptions(): array
    {
        $excluded = match (true) {
            $this->isAccessRequest() => array_merge(self::CHANGE_ONLY_STATUSES, self::CONTENT_ONLY_STATUSES),
            // Content requests do get scheduled — they skip the access training
            // states, and the change lane's intake and referral path.
            $this->isContentRequest() => array_merge(self::ACCESS_ONLY_STATUSES, self::REFERRAL_STATUSES),
            default => array_merge(self::ACCESS_ONLY_STATUSES, self::CONTENT_ONLY_STATUSES),
        };

        // Whatever it is on right now stays in the list even when excluded. A
        // request left in a status this lane no longer offers would otherwise
        // show a different option as selected, and Update would move it without
        // anyone meaning to.
        return array_values(array_filter(
            self::STATUSES,
            fn ($status) => !in_array($status, $excluded) || $status === $this->status,
        ));
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * The main home plus every additional site, in that order.
     */
    public function allSites()
    {
        $sites = collect();
        if ($this->site) {
            $sites->push($this->site);
        }

        return $sites->concat($this->additionalSites)->unique('id')->values();
    }

    /**
     * Where this content went live on a given site, if it has.
     */
    public function publishedFor(int $siteId): array
    {
        if ($siteId === $this->site_id) {
            return [
                'published_url' => $this->published_url,
                'published_title' => $this->published_title,
            ];
        }

        $site = $this->additionalSites->firstWhere('id', $siteId);

        return [
            'published_url' => $site?->pivot?->published_url,
            'published_title' => $site?->pivot?->published_title,
        ];
    }

    /**
     * Additional sites this content is published to. The main home stays on site_id.
     */
    public function additionalSites()
    {
        return $this->belongsToMany(Site::class, 'change_request_sites')
            ->withPivot(['published_url', 'published_title'])
            ->withTimestamps();
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

    /**
     * Files attached to the request itself rather than to a line item — how a
     * content brief carries the leaflet or document it is talking about.
     */
    public function files()
    {
        return $this->hasMany(ChangeRequestItemFile::class)->orderBy('created_at');
    }

    public function watchers()
    {
        return $this->hasMany(ChangeRequestWatcher::class);
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
