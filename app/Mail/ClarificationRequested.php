<?php

namespace App\Mail;

use App\Http\Controllers\PublicSite\ClarificationController;
use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ClarificationRequested extends Mailable
{

    public function __construct(
        public ChangeRequest $changeRequest,
    ) {
        $this->changeRequest->loadMissing(['site', 'items', 'cptType']);
    }

    public function envelope(): Envelope
    {
        $emailContent = Setting::getEmailContent('clarification_requested', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $emailContent = Setting::getEmailContent('clarification_requested', $this->placeholderValues());

        return new Content(
            view: 'emails.clarification-requested',
            with: [
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'pageTitle' => $this->changeRequest->page_title ?? $this->changeRequest->page_url,
                'isNewPage' => $this->changeRequest->is_new_page,
                'isAccessRequest' => $this->changeRequest->isAccessRequest(),
                'cptName' => $this->changeRequest->cptType->name ?? $this->changeRequest->cpt_slug,
                'recipientName' => $this->changeRequest->access_recipient_name,
                'clarificationMessage' => $this->changeRequest->clarification_message,
                'respondUrl' => ClarificationController::respondUrl($this->changeRequest),
                'customBody' => Setting::get('email_clarification_requested_body') ? $emailContent['body'] : null,
                'defaultBody' => $emailContent['body'],
            ],
        );
    }

    protected function placeholderValues(): array
    {
        $pageTitle = $this->changeRequest->isAccessRequest()
            ? ($this->changeRequest->cptType->name ?? $this->changeRequest->cpt_slug) . ' access request'
            : ($this->changeRequest->page_title ?? $this->changeRequest->page_url);

        return [
            'reference' => $this->changeRequest->reference,
            'site_name' => $this->changeRequest->site->name ?? 'Unknown site',
            'page_title' => $pageTitle,
            'clarification_message' => $this->changeRequest->clarification_message ?? '',
        ];
    }
}
