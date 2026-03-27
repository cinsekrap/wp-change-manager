<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_active', 'is_admin', 'provider', 'provider_id', 'mfa_secret', 'mfa_enabled', 'mfa_confirmed_at'])]
#[Hidden(['password', 'remember_token', 'mfa_secret'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
            'mfa_enabled' => 'boolean',
            'mfa_confirmed_at' => 'datetime',
        ];
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

    public function notes()
    {
        return $this->hasMany(ChangeRequestNote::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(ChangeRequestStatusLog::class);
    }
}
