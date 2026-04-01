<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ApprovalDeclined extends Mailable
{

    public function __construct(
        public ChangeRequest $changeRequest,
        public ChangeRequestApprover $approver,
    ) {}

    public function envelope(): Envelope
    {
        $this->changeRequest->loadMissing(['site', 'items']);

        $emailContent = Setting::getEmailContent('approval_declined', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $this->changeRequest->loadMissing(['site', 'items']);

        $emailContent = Setting::getEmailContent('approval_declined', $this->placeholderValues());
        $defaults = config('email-templates.approval_declined');

        return new Content(
            view: 'emails.approval-declined',
            with: [
                'approverName' => $this->approver->name,
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'pageTitle' => $this->changeRequest->page_title ?? $this->changeRequest->page_url,
                'customBody' => Setting::get('email_approval_declined_body') ? $emailContent['body'] : null,
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
            'approver_name' => $this->approver->name,
        ];
    }
}
