<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CptType extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'form_config', 'sort_order', 'is_active', 'is_blocked', 'blocked_message'];

    protected function casts(): array
    {
        return [
            'form_config' => 'array',
            'is_active' => 'boolean',
            'is_blocked' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
