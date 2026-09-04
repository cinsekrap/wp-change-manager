<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeRequestItemFile extends Model
{
    protected $fillable = ['change_request_id', 'change_request_item_id', 'original_filename', 'title', 'description', 'stored_path', 'mime_type', 'file_size', 'purged_at'];

    protected $casts = ['purged_at' => 'datetime'];

    public function item()
    {
        return $this->belongsTo(ChangeRequestItem::class, 'change_request_item_id');
    }

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class);
    }
}
