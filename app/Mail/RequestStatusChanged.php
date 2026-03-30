<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RequestStatusChanged extends Mailable
{

    public function __construct(
        public ChangeRequest $changeRequest,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function envelope(): Envelope
    {
        $this->changeRequest->loadMissing(['site', 'items']);

        $emailContent = Setting::getEmailContent('status_changed', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $this->changeRequest->loadMissing(['site', 'items']);

        $emailContent = Setting::getEmailContent('status_changed', $this->placeholderValues());
        $defaults = config('email-templates.status_changed');

        return new Content(
            view: 'emails.request-status-changed',
            with: [
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'pageTitle' => $this->changeRequest->page_title ?? $this->changeRequest->page_url,
                'isNewPage' => $this->changeRequest->is_new_page,
                'itemCount' => $this->changeRequest->items->count(),
                'items' => $this->changeRequest->items->take(5),
                'oldStatus' => ucfirst(str_replace('_', ' ', $this->oldStatus)),
                'newStatus' => ucfirst(str_replace('_', ' ', $this->newStatus)),
                'rejectionReason' => $this->changeRequest->rejection_reason,
                'trackingUrl' => \App\Http\Controllers\PublicSite\TrackingController::signedUrl($this->changeRequest),
                'customBody' => Setting::get('email_status_changed_body') ? $emailContent['body'] : null,
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
            'old_status' => ucfirst(str_replace('_', ' ', $this->oldStatus)),
            'new_status' => ucfirst(str_replace('_', ' ', $this->newStatus)),
            'rejection_reason' => $this->changeRequest->rejection_reason ?? '',
        ];
    }
}
