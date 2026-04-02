<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RequestSubmitted extends Mailable
{

    public function __construct(
        public ChangeRequest $changeRequest,
    ) {
        $this->changeRequest->loadMissing(['site', 'items']);
    }

    public function envelope(): Envelope
    {
        $emailContent = Setting::getEmailContent('request_submitted', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $emailContent = Setting::getEmailContent('request_submitted', $this->placeholderValues());
        $defaults = config('email-templates.request_submitted');

        return new Content(
            view: 'emails.request-submitted',
            with: [
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'pageTitle' => $this->changeRequest->page_title ?? $this->changeRequest->page_url,
                'itemCount' => $this->changeRequest->items->count(),
                'deadlineDate' => $this->changeRequest->deadline_date,
                'trackingUrl' => \App\Http\Controllers\PublicSite\TrackingController::signedUrl($this->changeRequest),
                'customBody' => Setting::get('email_request_submitted_body') ? $emailContent['body'] : null,
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
            'item_count' => (string) $this->changeRequest->items->count(),
            'deadline_date' => $this->changeRequest->deadline_date?->format('j M Y') ?? '',
        ];
    }
}
