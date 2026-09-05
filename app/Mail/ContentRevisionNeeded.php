<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * To the content designer, when a clinician does not approve the copy.
 *
 * They wrote it and they are the only person who can act on the feedback, so it
 * goes to them rather than to whoever suggested the page.
 */
class ContentRevisionNeeded extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ChangeRequest $changeRequest,
        public ChangeRequestApprover $approver,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailContent()['subject']);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.content-revision-needed',
            with: [
                'changeRequest' => $this->changeRequest,
                'reference' => $this->changeRequest->reference,
                'approverName' => $this->approver->name,
                'feedback' => $this->approver->notes,
                'adminUrl' => route('admin.requests.show', $this->changeRequest).'#draft',
                'bodyText' => $this->emailContent()['body'],
            ],
        );
    }

    private function emailContent(): array
    {
        return Setting::getEmailContent('content_revision_needed', [
            'reference' => $this->changeRequest->reference,
            'approver_name' => $this->approver->name,
            'content_title' => $this->changeRequest->subjectDescription(),
        ]);
    }
}
