<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewRequestAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ChangeRequest $changeRequest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Change Request: {$this->changeRequest->reference}",
        );
    }

    public function content(): Content
    {
        $this->changeRequest->loadMissing(['site', 'items']);

        return new Content(
            view: 'emails.new-request-alert',
            with: [
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'pageTitle' => $this->changeRequest->page_title ?? $this->changeRequest->page_url,
                'requesterName' => $this->changeRequest->requester_name,
                'requesterEmail' => $this->changeRequest->requester_email,
                'itemCount' => $this->changeRequest->items->count(),
                'adminUrl' => route('admin.requests.show', $this->changeRequest),
            ],
        );
    }
}
