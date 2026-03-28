<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name', 'colour'];

    public function changeRequests()
    {
        return $this->belongsToMany(ChangeRequest::class, 'change_request_tag')->withTimestamps();
    }
}
