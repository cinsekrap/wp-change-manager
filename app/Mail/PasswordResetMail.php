<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PasswordResetMail extends Mailable
{
    public function __construct(
        public string $resetUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: [
                'resetUrl' => $this->resetUrl,
            ],
        );
    }
}
