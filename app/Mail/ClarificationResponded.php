<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ClarificationResponded extends Mailable
{

    public function __construct(
        public ChangeRequest $changeRequest,
        public string $comment,
        public int $itemsUpdated,
    ) {
        $this->changeRequest->loadMissing(['site', 'cptType']);
    }

    public function envelope(): Envelope
    {
        $emailContent = Setting::getEmailContent('clarification_response', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $emailContent = Setting::getEmailContent('clarification_response', $this->placeholderValues());

        return new Content(
            view: 'emails.clarification-response',
            with: [
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'requesterName' => $this->changeRequest->requester_name,
                'newStatus' => ucfirst(str_replace('_', ' ', $this->changeRequest->status)),
                'comment' => $this->comment,
                'itemsUpdated' => $this->itemsUpdated,
                'adminUrl' => route('admin.requests.show', $this->changeRequest),
                'customBody' => Setting::get('email_clarification_response_body') ? $emailContent['body'] : null,
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
            'requester_name' => $this->changeRequest->requester_name,
            'response_comment' => $this->comment,
        ];
    }
}
