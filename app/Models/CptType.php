<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CptType extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'form_config', 'sort_order', 'is_active', 'request_mode', 'mode_message'];

    public const REQUEST_MODES = ['normal', 'blocked', 'self_service'];

    protected function casts(): array
    {
        return [
            'form_config' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isBlocked(): bool
    {
        return $this->request_mode === 'blocked';
    }

    public function isSelfService(): bool
    {
        return $this->request_mode === 'self_service';
    }

    public function isNormal(): bool
    {
        return $this->request_mode === 'normal' || !$this->request_mode;
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
