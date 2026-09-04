<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContentSuggestionReceived extends Mailable
{
    public function __construct(
        public ChangeRequest $changeRequest,
    ) {
        $this->changeRequest->loadMissing(['site', 'additionalSites']);
    }

    public function envelope(): Envelope
    {
        $emailContent = Setting::getEmailContent('content_suggestion_received', $this->placeholderValues());

        return new Envelope(subject: $emailContent['subject']);
    }

    public function content(): Content
    {
        $emailContent = Setting::getEmailContent('content_suggestion_received', $this->placeholderValues());

        return new Content(
            view: 'emails.content-suggestion-received',
            with: array_merge([
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'contentTypeLabel' => $this->contentTypeLabel(),
                'trackingUrl' => \App\Http\Controllers\PublicSite\TrackingController::signedUrl($this->changeRequest),
                'customBody' => Setting::get('email_content_suggestion_received_body') ? $emailContent['body'] : null,
                'defaultBody' => config('email-templates.content_suggestion_received.body'),
            ], $this->extraViewData()),
        );
    }

    private function contentTypeLabel(): string
    {
        $key = $this->changeRequest->content_type;

        return config("content-types.{$key}.label", 'New content');
    }

    private function publishedSites(): array
    {
        $sites = [];
        if ($this->changeRequest->site) {
            $sites[] = $this->changeRequest->site->name;
        }
        foreach ($this->changeRequest->additionalSites as $site) {
            $sites[] = $site->name;
        }

        return $sites;
    }

    private function extraViewData(): array
    {
        return [];
    }

    private function placeholderValues(): array
    {
        return [
            'reference' => $this->changeRequest->reference,
            'site_name' => $this->changeRequest->site->name ?? 'Unknown site',
            'content_type' => $this->contentTypeLabel(),
            'site_titles' => implode(', ', $this->publishedSites()),
        ];
    }
}
