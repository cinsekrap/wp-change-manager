<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeRequestNote extends Model
{
    protected $fillable = ['change_request_id', 'user_id', 'note'];

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
