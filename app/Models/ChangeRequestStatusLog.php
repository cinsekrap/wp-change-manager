<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeRequestStatusLog extends Model
{
    protected $table = 'change_request_status_log';

    protected $fillable = ['change_request_id', 'user_id', 'old_status', 'new_status'];

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
