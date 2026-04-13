<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class GroupApprovalSatisfied extends Mailable
{
    public function __construct(
        public ChangeRequest $changeRequest,
        public ChangeRequestApprover $approver,
        public string $satisfiedBy,
    ) {
        $this->changeRequest->loadMissing(['site']);
    }

    public function envelope(): Envelope
    {
        $emailContent = Setting::getEmailContent('group_approval_satisfied', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $emailContent = Setting::getEmailContent('group_approval_satisfied', $this->placeholderValues());
        $defaults = config('email-templates.group_approval_satisfied');

        return new Content(
            view: 'emails.group-approval-satisfied',
            with: [
                'approverName' => $this->approver->name,
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'pageTitle' => $this->changeRequest->page_title ?? $this->changeRequest->page_url,
                'customBody' => Setting::get('email_group_approval_satisfied_body') ? $emailContent['body'] : null,
                'defaultBody' => $defaults['body'],
                'satisfiedBy' => $this->satisfiedBy,
                'groupName' => $this->approver->group,
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
            'satisfied_by' => $this->satisfiedBy,
            'group_name' => $this->approver->group,
        ];
    }
}
