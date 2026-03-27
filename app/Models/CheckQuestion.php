<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckQuestion extends Model
{
    protected $fillable = ['question_text', 'options', 'sort_order', 'is_active', 'is_required'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_active' => 'boolean',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
