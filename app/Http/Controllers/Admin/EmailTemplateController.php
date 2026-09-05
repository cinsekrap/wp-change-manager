<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccessGranted;
use App\Mail\ApprovalDeclined;
use App\Mail\ApprovalOverridden;
use App\Mail\ApprovalRequested;
use App\Mail\ClarificationRequested;
use App\Mail\ClarificationResponded;
use App\Mail\NewRequestAlert;
use App\Mail\RequestChase;
use App\Mail\RequestOnHold;
use App\Mail\RequestStatusChanged;
use App\Mail\RequestSubmitted;
use App\Mail\ScheduledForActionToday;
use App\Mail\TrainingConfirmed;
use App\Mail\TrainingRequested;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\CptType;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    /**
     * Show the email template editor.
     */
    public function index()
    {
        $defaults = config('email-templates');
        $templates = [];

        foreach ($defaults as $key => $tpl) {
            $templates[$key] = [
                'name' => $tpl['name'],
                'description' => $tpl['description'],
                'placeholders' => $tpl['placeholders'],
                'default_subject' => $tpl['subject'],
                'default_body' => $tpl['body'],
                'subject' => Setting::get("email_{$key}_subject") ?? '',
                'body' => Setting::get("email_{$key}_body") ?? '',
            ];
        }

        return view('admin.settings.email-templates', compact('templates'));
    }

    /**
     * Save custom email template content.
     */
    public function update(Request $request)
    {
        $defaults = config('email-templates');

        foreach (array_keys($defaults) as $key) {
            $subject = $request->input("templates.{$key}.subject");
            $body = $request->input("templates.{$key}.body");

            // Only store if different from default (or if explicitly provided)
            if ($subject !== null && $subject !== '') {
                Setting::set("email_{$key}_subject", $subject);
            } else {
                Setting::where('key', "email_{$key}_subject")->delete();
            }

            if ($body !== null && $body !== '') {
                Setting::set("email_{$key}_body", $body);
            } else {
                Setting::where('key', "email_{$key}_body")->delete();
            }
        }

        AuditService::log(
            action: 'updated',
            description: 'Updated email templates',
        );

        return redirect()->route('admin.settings.email-templates')->with('success', 'Email templates saved.');
    }

    /**
     * Reset a single email template to its defaults.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'template' => 'required|string|in:' . implode(',', array_keys(config('email-templates'))),
        ]);

        $key = $request->input('template');
        Setting::where('key', "email_{$key}_subject")->delete();
        Setting::where('key', "email_{$key}_body")->delete();

        return redirect()->route('admin.settings.email-templates')->with('success', 'Template "' . config("email-templates.{$key}.name") . '" reset to default.');
    }

    /**
     * Render a live preview of a mailable template.
     */
    public function preview(string $template)
    {
        // Build a sample change request for preview
        $sample = ChangeRequest::with(['site', 'items'])->latest()->first();

        if (!$sample) {
            // Create a fake one in memory (not persisted)
            $sample = new ChangeRequest([
                'reference' => 'WCR-20260327-001',
                'page_url' => 'https://example.com/services/health-visiting',
                'page_title' => 'Health Visiting',
                'cpt_slug' => 'services',
                'status' => 'approved',
                'requester_name' => 'Jane Smith',
                'requester_email' => 'jane@example.com',
                'deadline_date' => now()->addDays(7),
                'scheduled_date' => now(),
                'created_at' => now()->subHours(72),
                'updated_at' => now()->subHours(48),
            ]);
            $sample->id = 0;
            $sample->setRelation('site', new \App\Models\Site(['name' => 'Example Site', 'domain' => 'example.com']));
            $sample->setRelation('items', collect());
        }

        // Sample assignee for the request-assigned preview
        $sampleAssignee = \App\Models\User::first() ?: new \App\Models\User([
            'name' => 'Sam Taylor',
            'email' => 'sam.taylor@example.com',
        ]);

        // Sample watcher for the watch-confirmation preview
        $sampleWatcher = new \App\Models\ChangeRequestWatcher([
            'email' => 'someone@example.com',
            'token' => 'sample-preview-token',
        ]);

        // Build a sample approver for the approval email preview
        $sampleApprover = new ChangeRequestApprover([
            'name' => 'Dr Helen Johal',
            'email' => 'h.johal@nhs.net',
            'token' => 'sample-preview-token',
        ]);
        $sampleApprover->setRelation('changeRequest', $sample);

        // Force access-request attributes in memory (not persisted) so the
        // training previews render even when the latest real request isn't one
        if (in_array($template, ['training-requested', 'training-confirmed', 'access-granted'])) {
            $sample->request_type = 'access';
            $sample->access_recipient_name = $sample->access_recipient_name ?: 'Jane Smith';
            $sample->access_recipient_email = $sample->access_recipient_email ?: 'jane@example.com';
            $sample->training_token = 'sample-preview-token';
            $sample->training_confirmed_at = now();
            $sample->setRelation('cptType', new CptType([
                'slug' => $sample->cpt_slug ?: 'events',
                'name' => 'Events',
                'training_url' => 'https://example.com/training-video',
            ]));
        }

        // Force content attributes in memory so the content previews render even
        // when the latest real request is a change request.
        if (str_starts_with($template, 'content-') || $template === 'watch-confirmation') {
            $sample->request_type = 'content';
            $sample->content_type = $sample->content_type ?: 'service_explainer';
            $sample->public_title = $sample->public_title ?: 'What happens at your first appointment';
            if (!$sample->relationLoaded('additionalSites')) {
                $sample->setRelation('additionalSites', collect());
            }
        }

        // Force on-hold attributes in memory so the preview always has a reason
        if ($template === 'request-on-hold') {
            $sample->status = 'on_hold';
            $sample->hold_reason = $sample->hold_reason ?: 'We are waiting for content sign-off from the service lead before we can make these changes.';
        }

        // Force clarification attributes in memory so the previews always have content
        if (in_array($template, ['clarification-requested', 'clarification-response'])) {
            $sample->status = $template === 'clarification-requested' ? 'awaiting_user' : 'approved';
            $sample->clarification_message = $sample->clarification_message
                ?: 'Could you confirm which of the two phone numbers on this page is correct?';
        }

        // A funding ask is about a batch, not one request, so its preview needs a
        // round rather than the shared sample. Built in memory only.
        if ($template === 'funding-requested') {
            $round = new \App\Models\FundingRound([
                'reference' => 'FR-'.now()->format('Ymd').'-001',
                'approver_name' => 'Sam Okafor — Head of Communications',
                'approver_email' => 'sam.okafor@example.com',
                'status' => 'pending',
                'total_hours' => 20,
            ]);
            $round->id = 0;
            $round->token = str_repeat('0', 64);

            $line = new \App\Models\FundingRoundItem(['estimated_hours' => 8]);
            $line->setRelation('changeRequest', $sample);
            $second = new \App\Models\FundingRoundItem(['estimated_hours' => 12]);
            $second->setRelation('changeRequest', $sample);
            $round->setRelation('items', collect([$line, $second]));

            return new \App\Mail\FundingRequested($round);
        }

        $mailable = match ($template) {
            'request-submitted' => new RequestSubmitted($sample),
            'status-changed' => new RequestStatusChanged($sample, 'requested', 'approved'),
            'request-on-hold' => new RequestOnHold($sample),
            'clarification-requested' => new ClarificationRequested($sample),
            'clarification-response' => new ClarificationResponded($sample, 'The second number is the right one — I have updated the wording too.', 1),
            'new-request-alert' => new NewRequestAlert($sample),
            'approval-requested' => new ApprovalRequested($sample, $sampleApprover),
            'approval-overridden' => new ApprovalOverridden($sample, $sampleApprover),
            'approval-declined' => new ApprovalDeclined($sample, $sampleApprover),
            'request-chase' => new RequestChase($sample),
            'scheduled-today' => new ScheduledForActionToday($sample),
            'training-requested' => new TrainingRequested($sample),
            'training-confirmed' => new TrainingConfirmed($sample),
            'access-granted' => new AccessGranted($sample),
            'content-suggestion-received' => new \App\Mail\ContentSuggestionReceived($sample),
            'content-revision-needed' => new \App\Mail\ContentRevisionNeeded($sample, $sampleApprover),
            'content-awaiting-funding' => new \App\Mail\ContentAwaitingFunding($sample),
            'content-published' => new \App\Mail\ContentPublished($sample),
            'watch-confirmation' => new \App\Mail\WatchConfirmation($sample, $sampleWatcher),
            // These two are in config but had no preview; generating the list surfaced them.
            'request-assigned' => new \App\Mail\RequestAssigned($sample, $sampleAssignee),
            'group-approval-satisfied' => new \App\Mail\GroupApprovalSatisfied($sample, $sampleApprover, 'Dr Helen Johal'),
            default => abort(404),
        };

        return $mailable->render();
    }
}
