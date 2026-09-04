<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestWatcher;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WatchConfirmation extends Mailable
{
    public function __construct(
        public ChangeRequest $changeRequest,
        public ChangeRequestWatcher $watcher,
    ) {
    }

    public function envelope(): Envelope
    {
        $emailContent = Setting::getEmailContent('watch_confirmation', $this->placeholderValues());

        return new Envelope(subject: $emailContent['subject']);
    }

    public function content(): Content
    {
        $emailContent = Setting::getEmailContent('watch_confirmation', $this->placeholderValues());

        return new Content(
            view: 'emails.watch-confirmation',
            with: [
                'reference' => $this->changeRequest->reference,
                'publicTitle' => $this->changeRequest->public_title,
                'confirmUrl' => route('suggestions.confirm', $this->watcher->token),
                'customBody' => Setting::get('email_watch_confirmation_body') ? $emailContent['body'] : null,
                'defaultBody' => config('email-templates.watch_confirmation.body'),
            ],
        );
    }

    private function placeholderValues(): array
    {
        return [
            'reference' => $this->changeRequest->reference,
            'public_title' => (string) $this->changeRequest->public_title,
        ];
    }
}
