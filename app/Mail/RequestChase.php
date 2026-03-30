<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RequestChase extends Mailable
{

    public function __construct(
        public ChangeRequest $changeRequest,
    ) {}

    public function envelope(): Envelope
    {
        $this->changeRequest->loadMissing(['site']);

        $emailContent = Setting::getEmailContent('request_chase', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $this->changeRequest->loadMissing(['site']);

        $emailContent = Setting::getEmailContent('request_chase', $this->placeholderValues());
        $defaults = config('email-templates.request_chase');

        return new Content(
            view: 'emails.request-chase',
            with: [
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'pageTitle' => $this->changeRequest->page_title ?? $this->changeRequest->page_url,
                'status' => ucfirst(str_replace('_', ' ', $this->changeRequest->status)),
                'staleHours' => $this->changeRequest->updated_at->diffInHours(now()),
                'requesterName' => $this->changeRequest->requester_name,
                'requesterEmail' => $this->changeRequest->requester_email,
                'adminUrl' => route('admin.requests.show', $this->changeRequest),
                'customBody' => Setting::get('email_request_chase_body') ? $emailContent['body'] : null,
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
            'status' => ucfirst(str_replace('_', ' ', $this->changeRequest->status)),
            'stale_hours' => (string) $this->changeRequest->updated_at->diffInHours(now()),
            'requester_name' => $this->changeRequest->requester_name,
            'requester_email' => $this->changeRequest->requester_email,
        ];
    }
}
