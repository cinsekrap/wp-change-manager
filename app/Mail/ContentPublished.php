<?php

namespace App\Mail;

use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContentPublished extends Mailable
{
    public function __construct(
        public ChangeRequest $changeRequest,
        // Set when this is going to a watcher rather than the suggester, so the
        // email can carry their unsubscribe link.
        public ?\App\Models\ChangeRequestWatcher $watcher = null,
    ) {
        $this->changeRequest->loadMissing(['site', 'additionalSites']);
    }

    public function envelope(): Envelope
    {
        $emailContent = Setting::getEmailContent('content_published', $this->placeholderValues());

        return new Envelope(subject: $emailContent['subject']);
    }

    public function content(): Content
    {
        $emailContent = Setting::getEmailContent('content_published', $this->placeholderValues());

        return new Content(
            view: 'emails.content-published',
            with: array_merge([
                'reference' => $this->changeRequest->reference,
                'siteName' => $this->changeRequest->site->name ?? 'Unknown site',
                'contentTypeLabel' => $this->contentTypeLabel(),
                'trackingUrl' => \App\Http\Controllers\PublicSite\TrackingController::signedUrl($this->changeRequest),
                'unsubscribeUrl' => $this->watcher
                    ? route('suggestions.unsubscribe', $this->watcher->token)
                    : null,
                'customBody' => Setting::get('email_content_published_body') ? $emailContent['body'] : null,
                'defaultBody' => config('email-templates.content_published.body'),
            ], $this->extraViewData()),
        );
    }

    private function contentTypeLabel(): string
    {
        $key = $this->changeRequest->content_type;

        return config("content-types.{$key}.label", 'New content');
    }

    /**
     * Where it actually went live. Only sites with a recorded address are listed —
     * naming a site with no URL asserts a publication that may not have happened.
     */
    private function publishedSites(): array
    {
        return $this->changeRequest->allSites()
            ->map(function ($site) {
                $published = $this->changeRequest->publishedFor($site->id);

                return [
                    'site' => $site->name,
                    'title' => $published['published_title'] ?: null,
                    'url' => $published['published_url'] ?: null,
                ];
            })
            ->filter(fn ($row) => $row['url'] !== null)
            ->values()
            ->all();
    }

    private function extraViewData(): array
    {
        return ['publishedSites' => $this->publishedSites()];
    }

    private function placeholderValues(): array
    {
        return [
            'reference' => $this->changeRequest->reference,
            'site_name' => $this->changeRequest->site->name ?? 'Unknown site',
            'content_type' => $this->contentTypeLabel(),
            'site_titles' => implode(', ', array_column($this->publishedSites(), 'site')),
        ];
    }
}
