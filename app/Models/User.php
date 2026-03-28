<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_active', 'role', 'provider', 'provider_id', 'mfa_secret', 'mfa_enabled', 'mfa_confirmed_at'])]
#[Hidden(['password', 'remember_token', 'mfa_secret'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_EDITOR = 'editor';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'mfa_secret' => 'encrypted',
            'mfa_enabled' => 'boolean',
            'mfa_confirmed_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_EDITOR]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isEditor(): bool
    {
        return $this->role === self::ROLE_EDITOR;
    }

    public function sendPasswordResetNotification($token): void
    {
        $url = url(route('password.reset', ['token' => $token, 'email' => $this->email], false));

        \Illuminate\Support\Facades\Mail::to($this->email)
            ->send(new \App\Mail\PasswordResetMail($url));
    }

    public function hasMfaEnabled(): bool
    {
        return $this->mfa_enabled && $this->mfa_confirmed_at !== null;
    }

    public function assignedRequests()
    {
        return $this->hasMany(ChangeRequest::class, 'assigned_to');
    }

    public function notes()
    {
        return $this->hasMany(ChangeRequestNote::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(ChangeRequestStatusLog::class);
    }
}
