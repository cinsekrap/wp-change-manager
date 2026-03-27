<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ChangeRequest $changeRequest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Change Request {$this->changeRequest->reference} — Submitted",
        );
    }

    public function content(): Content
    {
        $this->changeRequest->loadMissing(['site', 'items']);

        return new Content(
            view: 'emails.request-submitted',
            with: [
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'pageTitle' => $this->changeRequest->page_title ?? $this->changeRequest->page_url,
                'itemCount' => $this->changeRequest->items->count(),
                'deadlineDate' => $this->changeRequest->deadline_date,
                'trackingUrl' => \App\Http\Controllers\PublicSite\TrackingController::signedUrl($this->changeRequest),
            ],
        );
    }
}
