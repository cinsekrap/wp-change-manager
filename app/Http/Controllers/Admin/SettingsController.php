<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ApprovalRequested;
use App\Mail\NewRequestAlert;
use App\Mail\RequestStatusChanged;
use App\Mail\RequestSubmitted;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    public function edit()
    {
        $settings = Setting::getMany([
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
        ]);

        return view('admin.settings.mail', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'mail_host'         => 'required|string|max:255',
            'mail_port'         => 'required|integer|min:1|max:65535',
            'mail_encryption'   => 'required|in:tls,ssl,none',
            'mail_username'     => 'nullable|string|max:255',
            'mail_password'     => 'nullable|string|max:255',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name'    => 'required|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            // Don't overwrite the password if the field was left empty
            if ($key === 'mail_password' && empty($value)) {
                continue;
            }
            Setting::set($key, $value);
        }

        return redirect()->route('admin.settings.mail')->with('success', 'Mail settings saved.');
    }

    public function test(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            Mail::raw('This is a test email from ' . config('app.name') . '. Your SMTP settings are working correctly.', function ($message) use ($request) {
                $message->to($request->test_email)
                        ->subject('Test Email — ' . config('app.name'));
            });

            return back()->with('test_success', 'Test email sent successfully to ' . $request->test_email);
        } catch (\Exception $e) {
            return back()->with('test_error', 'Failed to send test email: ' . $e->getMessage())
                         ->withInput();
        }
    }

    public function previewEmail(string $template)
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
            ]);
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
            default => abort(404),
        };

        return $mailable->render();
    }
}
