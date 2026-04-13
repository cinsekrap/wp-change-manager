<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeRequestItem extends Model
{
    public const STATUSES = ['in_progress', 'done', 'not_done'];

    protected $fillable = ['change_request_id', 'action_type', 'content_area', 'description', 'current_content', 'sort_order', 'status'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'status' => 'string',
        ];
    }

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function files()
    {
        return $this->hasMany(ChangeRequestItemFile::class);
    }
}
