<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RequestAssigned extends Mailable
{

    public function __construct(
        public ChangeRequest $changeRequest,
        public User $assignee,
    ) {
        $this->changeRequest->loadMissing(['site']);
    }

    public function envelope(): Envelope
    {
        $emailContent = Setting::getEmailContent('request_assigned', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $emailContent = Setting::getEmailContent('request_assigned', $this->placeholderValues());
        $defaults = config('email-templates.request_assigned');

        return new Content(
            view: 'emails.request-assigned',
            with: [
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'pageTitle' => $this->changeRequest->page_title ?? $this->changeRequest->page_url,
                'requesterName' => $this->changeRequest->requester_name,
                'assigneeName' => $this->assignee->name,
                'adminUrl' => route('admin.requests.show', $this->changeRequest),
                'customBody' => Setting::get('email_request_assigned_body') ? $emailContent['body'] : null,
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
            'assignee_name' => $this->assignee->name,
        ];
    }
}
