<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ApprovalDeclined;
use App\Mail\ApprovalOverridden;
use App\Mail\ApprovalRequested;
use App\Mail\NewRequestAlert;
use App\Mail\RequestChase;
use App\Mail\RequestStatusChanged;
use App\Mail\RequestSubmitted;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
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
                'created_at' => now()->subHours(72),
                'updated_at' => now()->subHours(48),
            ]);
            $sample->id = 0;
            $sample->setRelation('site', new \App\Models\Site(['name' => 'Example Site', 'domain' => 'example.com']));
            $sample->setRelation('items', collect());
        }

        // Build a sample approver for the approval email preview
        $sampleApprover = new ChangeRequestApprover([
            'name' => 'Dr Helen Johal',
            'email' => 'h.johal@nhs.net',
            'token' => 'sample-preview-token',
        ]);
        $sampleApprover->setRelation('changeRequest', $sample);

        $mailable = match ($template) {
            'request-submitted' => new RequestSubmitted($sample),
            'status-changed' => new RequestStatusChanged($sample, 'requested', 'approved'),
            'new-request-alert' => new NewRequestAlert($sample),
            'approval-requested' => new ApprovalRequested($sample, $sampleApprover),
            'approval-overridden' => new ApprovalOverridden($sample, $sampleApprover),
            'approval-declined' => new ApprovalDeclined($sample, $sampleApprover),
            'request-chase' => new RequestChase($sample),
            default => abort(404),
        };

        return $mailable->render();
    }
}
