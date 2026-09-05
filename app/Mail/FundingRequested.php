<?php

namespace App\Mail;

use App\Models\FundingRound;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FundingRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FundingRound $round) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailContent()['subject']);
    }

    /** Subject and body with the placeholders filled in, as the editor saved them. */
    private function emailContent(): array
    {
        return Setting::getEmailContent('funding_requested', [
            'reference' => $this->round->reference,
            'total_hours' => $this->hours(),
            'item_count' => (string) $this->round->items->count(),
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.funding-requested',
            with: [
                'round' => $this->round,
                'reference' => $this->round->reference,
                'totalHours' => $this->hours(),
                'itemCount' => $this->round->items->count(),
                'approvalUrl' => $this->round->token ? route('funding.show', $this->round->token) : null,
                'bodyText' => $this->emailContent()['body'],
            ],
        );
    }

    private function hours(): string
    {
        return rtrim(rtrim(number_format((float) $this->round->total_hours, 1), '0'), '.') ?: '0';
    }
}
