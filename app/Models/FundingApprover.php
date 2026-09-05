<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Someone who can agree to spend content design hours.
 *
 * A managed list rather than free text, for the same reason clinical approvers
 * are: a record saying hours were signed off by a name somebody typed proves
 * very little.
 */
class FundingApprover extends Model
{
    protected $fillable = ['name', 'email', 'job_title', 'remit', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /** Name and role together, so the record says who signed off, not just a name. */
    public function label(): string
    {
        return $this->job_title ? "{$this->name} — {$this->job_title}" : $this->name;
    }
}
