<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RequestOnHold extends Mailable
{

    public function __construct(
        public ChangeRequest $changeRequest,
    ) {
        $this->changeRequest->loadMissing(['site', 'items', 'cptType']);
    }

    public function envelope(): Envelope
    {
        $emailContent = Setting::getEmailContent('request_on_hold', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $emailContent = Setting::getEmailContent('request_on_hold', $this->placeholderValues());

        return new Content(
            view: 'emails.request-on-hold',
            with: [
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'pageTitle' => $this->changeRequest->subjectDescription(),
                'isNewPage' => $this->changeRequest->is_new_page,
                'isAccessRequest' => $this->changeRequest->isAccessRequest(),
                'isContentRequest' => $this->changeRequest->isContentRequest(),
                'cptName' => $this->changeRequest->cptType->name ?? $this->changeRequest->cpt_slug,
                'recipientName' => $this->changeRequest->access_recipient_name,
                'holdReason' => $this->changeRequest->hold_reason,
                'trackingUrl' => \App\Http\Controllers\PublicSite\TrackingController::signedUrl($this->changeRequest),
                'customBody' => Setting::get('email_request_on_hold_body') ? $emailContent['body'] : null,
                'defaultBody' => $emailContent['body'],
            ],
        );
    }

    protected function placeholderValues(): array
    {
        $pageTitle = $this->changeRequest->isAccessRequest()
            ? ($this->changeRequest->cptType->name ?? $this->changeRequest->cpt_slug) . ' access request'
            : ($this->changeRequest->subjectDescription());

        return [
            'reference' => $this->changeRequest->reference,
            'site_name' => $this->changeRequest->site->name ?? 'Unknown site',
            'page_title' => $pageTitle,
            'hold_reason' => $this->changeRequest->hold_reason ?? '',
        ];
    }
}
