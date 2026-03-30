<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewRequestAlert extends Mailable
{

    public function __construct(
        public ChangeRequest $changeRequest,
    ) {}

    public function envelope(): Envelope
    {
        $this->changeRequest->loadMissing(['site', 'items']);

        $emailContent = Setting::getEmailContent('new_request_alert', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $this->changeRequest->loadMissing(['site', 'items']);

        $emailContent = Setting::getEmailContent('new_request_alert', $this->placeholderValues());
        $defaults = config('email-templates.new_request_alert');

        return new Content(
            view: 'emails.new-request-alert',
            with: [
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'pageTitle' => $this->changeRequest->page_title ?? $this->changeRequest->page_url,
                'requesterName' => $this->changeRequest->requester_name,
                'requesterEmail' => $this->changeRequest->requester_email,
                'itemCount' => $this->changeRequest->items->count(),
                'adminUrl' => route('admin.requests.show', $this->changeRequest),
                'customBody' => Setting::get('email_new_request_alert_body') ? $emailContent['body'] : null,
                'defaultBody' => $defaults['body'],
            ],
        );
    }

    protected function placeholderValues(): array
    {
        return [
            'reference' => $this->changeRequest->reference,
            'site_name' => $this->changeRequest->site->name ?? 'Unknown site',
            'page_title' => $this->changeRequest->page_title ?? $this->changeRequest->page_url,
            'requester_name' => $this->changeRequest->requester_name,
            'requester_email' => $this->changeRequest->requester_email,
            'item_count' => (string) $this->changeRequest->items->count(),
        ];
    }
}
