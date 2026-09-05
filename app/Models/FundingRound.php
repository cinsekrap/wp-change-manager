<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * One ask for money, covering several pieces of content.
 *
 * The hours are copied onto the round and onto each line when the ask is made.
 * An approval binds to what the approver was shown — the same reasoning as the
 * clinical hash — so re-estimating a piece afterwards cannot quietly change what
 * somebody agreed to pay for.
 */
class FundingRound extends Model
{
    protected $fillable = [
        'reference', 'funding_approver_id', 'approver_name', 'approver_email',
        'status', 'token', 'total_hours', 'requested_by', 'notes', 'responded_at',
    ];

    protected $casts = ['responded_at' => 'datetime'];

    public function items()
    {
        return $this->hasMany(FundingRoundItem::class);
    }

    public function changeRequests()
    {
        return $this->belongsToMany(ChangeRequest::class, 'funding_round_items')
            ->withPivot('estimated_hours')
            ->withTimestamps();
    }

    public function approver()
    {
        return $this->belongsTo(FundingApprover::class, 'funding_approver_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function generateReference(): string
    {
        return DB::transaction(function () {
            $prefix = 'FR-'.now()->format('Ymd').'-';
            $count = static::where('reference', 'like', "{$prefix}%")->lockForUpdate()->count();

            return $prefix.str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Lines whose estimate has been changed since the ask went out. The approval
     * still covers the figure that was shown; this is how anyone notices.
     */
    public function driftedItems()
    {
        return $this->items->filter(
            fn (FundingRoundItem $item) => $item->changeRequest
                && (float) $item->changeRequest->estimated_hours !== (float) $item->estimated_hours
        );
    }
}
