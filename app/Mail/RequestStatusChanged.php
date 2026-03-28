<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ChangeRequest $changeRequest,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function envelope(): Envelope
    {
        $label = ucfirst(str_replace('_', ' ', $this->newStatus));

        return new Envelope(
            subject: "Change Request {$this->changeRequest->reference} — {$label}",
        );
    }

    public function content(): Content
    {
        $this->changeRequest->loadMissing(['site', 'items']);

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
            ],
        );
    }
}
