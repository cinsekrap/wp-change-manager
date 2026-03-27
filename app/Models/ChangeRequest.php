<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChangeRequest extends Model
{
    protected $fillable = [
        'reference', 'site_id', 'page_url', 'page_title', 'cpt_slug',
        'is_new_page', 'status', 'rejection_reason', 'requester_name', 'requester_email',
        'requester_phone', 'requester_role', 'check_answers',
        'deadline_date', 'deadline_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_new_page' => 'boolean',
            'check_answers' => 'array',
            'deadline_date' => 'date',
        ];
    }

    public const STATUSES = ['requested', 'requires_referral', 'referred', 'approved', 'scheduled', 'done', 'declined', 'cancelled'];

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
        return $this->approvalsAllApproved();
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
