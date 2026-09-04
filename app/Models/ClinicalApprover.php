<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalApprover extends Model
{
    protected $fillable = ['name', 'email', 'job_title', 'areas_of_expertise', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Name and role together, for pickers and for the record of who approved.
     */
    public function label(): string
    {
        return $this->job_title ? "{$this->name} — {$this->job_title}" : $this->name;
    }
}
