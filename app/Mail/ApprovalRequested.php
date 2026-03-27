<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApprovalRequested extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ChangeRequest $changeRequest,
        public ChangeRequestApprover $approver,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Approval Requested: {$this->changeRequest->reference}",
        );
    }

    public function content(): Content
    {
        $this->changeRequest->loadMissing(['site', 'items']);

        return new Content(
            view: 'emails.approval-requested',
            with: [
                'approverName' => $this->approver->name,
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'pageTitle' => $this->changeRequest->page_title ?? $this->changeRequest->page_url,
                'isNewPage' => $this->changeRequest->is_new_page,
                'requesterName' => $this->changeRequest->requester_name,
                'itemCount' => $this->changeRequest->items->count(),
                'deadlineDate' => $this->changeRequest->deadline_date,
                'approvalUrl' => route('approval.show', $this->approver->token),
            ],
        );
    }
}
