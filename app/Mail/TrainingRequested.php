<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TrainingRequested extends Mailable
{

    public function __construct(
        public ChangeRequest $changeRequest,
    ) {
        $this->changeRequest->loadMissing(['site', 'cptType']);
    }

    public function envelope(): Envelope
    {
        $emailContent = Setting::getEmailContent('training_requested', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $emailContent = Setting::getEmailContent('training_requested', $this->placeholderValues());

        return new Content(
            view: 'emails.training-requested',
            with: [
                'recipientName' => $this->changeRequest->access_recipient_name,
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'cptName' => $this->changeRequest->cptType->name ?? $this->changeRequest->cpt_slug,
                'requesterName' => $this->changeRequest->requester_name,
                'trainingUrl' => $this->changeRequest->cptType->training_url ?? '',
                'confirmUrl' => route('training.show', $this->changeRequest->training_token),
                'customBody' => Setting::get('email_training_requested_body') ? $emailContent['body'] : null,
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
            'requester_name' => $this->changeRequest->requester_name,
            'training_url' => $this->changeRequest->cptType->training_url ?? '',
            'confirm_url' => route('training.show', $this->changeRequest->training_token),
        ];
    }
}
