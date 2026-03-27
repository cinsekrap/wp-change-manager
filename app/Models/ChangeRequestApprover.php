<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeRequestApprover extends Model
{
    protected $fillable = [
        'change_request_id', 'name', 'email', 'status', 'notes', 'responded_at', 'recorded_by', 'token',
    ];

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public const STATUSES = ['pending', 'approved', 'rejected'];

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function recordedByUser()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
