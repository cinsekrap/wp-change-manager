<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AccessGranted extends Mailable
{

    public function __construct(
        public ChangeRequest $changeRequest,
    ) {
        $this->changeRequest->loadMissing(['site', 'cptType']);
    }

    public function envelope(): Envelope
    {
        $emailContent = Setting::getEmailContent('access_granted', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $emailContent = Setting::getEmailContent('access_granted', $this->placeholderValues());
        $domain = $this->changeRequest->site->domain ?? null;

        return new Content(
            view: 'emails.access-granted',
            with: [
                'recipientName' => $this->changeRequest->access_recipient_name,
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'cptName' => $this->changeRequest->cptType->name ?? $this->changeRequest->cpt_slug,
                'loginUrl' => $domain ? "https://{$domain}/wp-admin" : null,
                'customBody' => Setting::get('email_access_granted_body') ? $emailContent['body'] : null,
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
        ];
    }
}
