<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ScheduledForActionToday extends Mailable
{
    public function __construct(
        public ChangeRequest $changeRequest,
    ) {
        $this->changeRequest->loadMissing(['site', 'assignee']);
    }

    public function envelope(): Envelope
    {
        $emailContent = Setting::getEmailContent('scheduled_today', $this->placeholderValues());

        return new Envelope(
            subject: $emailContent['subject'],
        );
    }

    public function content(): Content
    {
        $emailContent = Setting::getEmailContent('scheduled_today', $this->placeholderValues());
        $defaults = config('email-templates.scheduled_today');

        return new Content(
            view: 'emails.scheduled-for-action-today',
            with: [
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'pageTitle' => $this->changeRequest->subjectDescription(),
                'scheduledDate' => $this->changeRequest->scheduled_date?->format('d M Y') ?? '—',
                'assigneeName' => $this->changeRequest->assignee->name ?? 'there',
                'requesterName' => $this->changeRequest->requester_name,
                'requesterEmail' => $this->changeRequest->requester_email,
                'adminUrl' => route('admin.requests.show', $this->changeRequest),
                'customBody' => Setting::get('email_scheduled_today_body') ? $emailContent['body'] : null,
                'defaultBody' => $emailContent['body'],
            ],
        );
    }

    protected function placeholderValues(): array
    {
        return [
            'reference' => $this->changeRequest->reference,
            'site_name' => $this->changeRequest->site->name ?? 'Unknown site',
            'page_title' => $this->changeRequest->subjectDescription(),
            'scheduled_date' => $this->changeRequest->scheduled_date?->format('d M Y') ?? '',
            'assignee_name' => $this->changeRequest->assignee->name ?? '',
        ];
    }
}
