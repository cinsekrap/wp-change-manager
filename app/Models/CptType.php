<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CptType extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'form_config', 'sort_order', 'is_active', 'request_mode', 'mode_message', 'training_url', 'content_kinds'];

    public const REQUEST_MODES = ['normal', 'blocked', 'self_service'];

    /**
     * The page type a kind of content is written into, or null when no page type
     * claims it — in which case the copy is drafted as free text.
     */
    public static function forContentKind(?string $kind): ?self
    {
        if (! $kind) {
            return null;
        }

        return static::whereNotNull('content_kinds')
            ->get()
            ->first(fn (self $type) => in_array($kind, $type->content_kinds ?? [], true));
    }

    protected function casts(): array
    {
        return [
            'form_config' => 'array',
            'content_kinds' => 'array',
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
