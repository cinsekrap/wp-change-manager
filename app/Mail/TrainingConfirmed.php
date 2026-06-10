<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TrainingConfirmed extends Mailable
{

    public function __construct(
        public ChangeRequest $changeRequest,
    ) {
        $this->changeRequest->loadMissing(['site', 'cptType']);
    }

    public function envelope(): Envelope
    {
        $emailContent = Setting::getEmailContent('training_confirmed', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $emailContent = Setting::getEmailContent('training_confirmed', $this->placeholderValues());

        return new Content(
            view: 'emails.training-confirmed',
            with: [
                'recipientName' => $this->changeRequest->access_recipient_name,
                'recipientEmail' => $this->changeRequest->access_recipient_email,
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'cptName' => $this->changeRequest->cptType->name ?? $this->changeRequest->cpt_slug,
                'confirmedAt' => $this->changeRequest->training_confirmed_at,
                'adminUrl' => route('admin.requests.show', $this->changeRequest),
                'customBody' => Setting::get('email_training_confirmed_body') ? $emailContent['body'] : null,
                'defaultBody' => $emailContent['body'],
            ],
        );
    }

    protected function placeholderValues(): array
    {
        return [
            'reference' => $this->changeRequest->reference,
            'site_name' => $this->changeRequest->site->name ?? 'Unknown site',
            'cpt_name' => $this->changeRequest->cptType->name ?? $this->changeRequest->cpt_slug,
            'recipient_name' => $this->changeRequest->access_recipient_name ?? '',
            'recipient_email' => $this->changeRequest->access_recipient_email ?? '',
            'confirmed_at' => $this->changeRequest->training_confirmed_at?->format('j M Y H:i') ?? '',
        ];
    }
}
