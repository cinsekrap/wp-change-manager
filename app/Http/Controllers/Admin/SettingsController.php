<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ApprovalOverridden;
use App\Mail\ApprovalRequested;
use App\Mail\NewRequestAlert;
use App\Mail\RequestChase;
use App\Mail\RequestStatusChanged;
use App\Mail\RequestSubmitted;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequest;
use App\Models\CheckQuestion;
use App\Models\CptType;
use App\Models\EmailLog;
use App\Models\Setting;
use App\Models\Tag;
use App\Services\AuditService;
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

    /**
     * Show the notifications settings page (templates hub, SLA, chase reminders).
     */
    public function notifications()
    {
        return view('admin.settings.notifications');
    }

    public function emailLog(Request $request)
    {
        $logs = EmailLog::with('changeRequest')
            ->when($request->search, function ($q, $search) {
                $q->where('recipient_email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.settings.email-log', compact('logs'));
    }

    public function emailLogShow(EmailLog $emailLog)
    {
        return $emailLog->body_html;
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

        AuditService::log(
            action: 'updated',
            description: 'Updated mail settings',
            newValues: array_diff_key($validated, ['mail_password' => '']),
        );

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
            'request-chase' => new RequestChase($sample),
            default => abort(404),
        };

        return $mailable->render();
    }

    /**
     * Save chase reminder settings.
     */
    public function updateChase(Request $request)
    {
        $validated = $request->validate([
            'chase_enabled' => 'nullable|boolean',
            'chase_hours' => 'required|integer|min:1|max:9999',
            'chase_unassigned_email' => 'nullable|email|max:255',
        ]);

        Setting::set('chase_enabled', !empty($validated['chase_enabled']) ? '1' : '0');
        Setting::set('chase_hours', (string) $validated['chase_hours']);
        Setting::set('chase_unassigned_email', $validated['chase_unassigned_email'] ?? '');

        return redirect()->route('admin.settings.notifications')->with('success', 'Chase reminder settings saved.');
    }

    /**
     * Save SLA turnaround times.
     */
    public function updateSla(Request $request)
    {
        $priorities = ChangeRequest::PRIORITIES;

        foreach ($priorities as $priority) {
            $key = "sla_{$priority}";
            $value = $request->input($key);

            if ($value !== null && $value !== '') {
                Setting::set($key, (int) $value);
            }
        }

        return redirect()->route('admin.settings.notifications')->with('success', 'SLA settings saved.');
    }

    /**
     * Show the email template editor.
     */
    public function emailTemplates()
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
    public function updateEmailTemplates(Request $request)
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
    public function resetEmailTemplate(Request $request)
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
     * Show the configuration import/export page.
     */
    public function configPage()
    {
        $counts = [
            'cpt_types' => CptType::count(),
            'check_questions' => CheckQuestion::count(),
            'tags' => Tag::count(),
        ];

        return view('admin.settings.config', compact('counts'));
    }

    /**
     * Export configuration as a JSON file download.
     */
    public function exportConfig(Request $request)
    {
        $sections = $request->input('sections', []);

        $data = [
            'exported_at' => now()->toIso8601String(),
            'version' => config('version.current'),
        ];

        // Sensitive keys that must never be exported
        $sensitiveKeys = ['mail_password', 'entra_client_secret'];

        if (in_array('cpt_types', $sections)) {
            $data['cpt_types'] = CptType::ordered()->get()->map(function ($cpt) {
                return [
                    'slug' => $cpt->slug,
                    'name' => $cpt->name,
                    'description' => $cpt->description,
                    'form_config' => $cpt->form_config,
                    'sort_order' => $cpt->sort_order,
                    'is_active' => $cpt->is_active,
                    'request_mode' => $cpt->request_mode ?? 'normal',
                    'mode_message' => $cpt->mode_message,
                ];
            })->toArray();
        }

        if (in_array('check_questions', $sections)) {
            $data['check_questions'] = CheckQuestion::ordered()->get()->map(function ($q) {
                return [
                    'question_text' => $q->question_text,
                    'options' => $q->options,
                    'sort_order' => $q->sort_order,
                    'is_active' => $q->is_active,
                    'is_required' => $q->is_required,
                ];
            })->toArray();
        }

        if (in_array('settings', $sections)) {
            $allSettings = Setting::all()->pluck('value', 'key');
            $exportableSettings = [];

            foreach ($allSettings as $key => $value) {
                // Skip sensitive keys
                if (in_array($key, $sensitiveKeys)) {
                    continue;
                }
                // Skip email template customisations (exported separately)
                if (str_starts_with($key, 'email_')) {
                    continue;
                }
                $exportableSettings[$key] = Setting::get($key);
            }

            $data['settings'] = $exportableSettings;
        }

        if (in_array('email_templates', $sections)) {
            $defaults = config('email-templates');
            $customised = [];

            foreach (array_keys($defaults) as $key) {
                $subject = Setting::get("email_{$key}_subject");
                $body = Setting::get("email_{$key}_body");

                if ($subject || $body) {
                    $customised[$key] = [];
                    if ($subject) {
                        $customised[$key]['subject'] = $subject;
                    }
                    if ($body) {
                        $customised[$key]['body'] = $body;
                    }
                }
            }

            $data['email_templates'] = $customised;
        }

        if (in_array('tags', $sections)) {
            $data['tags'] = Tag::orderBy('name')->get()->map(function ($tag) {
                return [
                    'name' => $tag->name,
                    'colour' => $tag->colour,
                ];
            })->toArray();
        }

        $filename = 'acme-change-config-' . now()->format('Y-m-d') . '.json';

        AuditService::log(
            action: 'exported',
            description: 'Exported configuration (' . implode(', ', $sections) . ')',
        );

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Import configuration from a JSON file upload.
     */
    public function importConfig(Request $request)
    {
        $request->validate([
            'config_file' => 'required|file|mimes:json,txt|max:2048',
        ]);

        $file = $request->file('config_file');
        $json = file_get_contents($file->getRealPath());
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()->route('admin.settings.config')
                ->with('error', 'Invalid JSON file: ' . json_last_error_msg());
        }

        if (!is_array($data) || !isset($data['version'])) {
            return redirect()->route('admin.settings.config')
                ->with('error', 'Invalid configuration file: missing version field.');
        }

        $sensitiveKeys = ['mail_password', 'entra_client_secret'];
        $sections = $request->input('import_sections', []);
        $summary = [];

        // Import CPT Types
        if (in_array('cpt_types', $sections) && !empty($data['cpt_types'])) {
            $created = 0;
            $updated = 0;

            foreach ($data['cpt_types'] as $cptData) {
                if (empty($cptData['slug'])) {
                    continue;
                }

                $existing = CptType::where('slug', $cptData['slug'])->first();

                if ($existing) {
                    $existing->update([
                        'name' => $cptData['name'] ?? $existing->name,
                        'description' => $cptData['description'] ?? $existing->description,
                        'form_config' => $cptData['form_config'] ?? $existing->form_config,
                        'sort_order' => $cptData['sort_order'] ?? $existing->sort_order,
                        'is_active' => $cptData['is_active'] ?? $existing->is_active,
                        'request_mode' => $cptData['request_mode'] ?? (!empty($cptData['is_blocked']) ? 'blocked' : ($existing->request_mode ?? 'normal')),
                        'mode_message' => $cptData['mode_message'] ?? $cptData['blocked_message'] ?? $existing->mode_message,
                    ]);
                    $updated++;
                } else {
                    CptType::create([
                        'slug' => $cptData['slug'],
                        'name' => $cptData['name'] ?? $cptData['slug'],
                        'description' => $cptData['description'] ?? null,
                        'form_config' => $cptData['form_config'] ?? null,
                        'sort_order' => $cptData['sort_order'] ?? 0,
                        'is_active' => $cptData['is_active'] ?? true,
                        'request_mode' => $cptData['request_mode'] ?? (!empty($cptData['is_blocked']) ? 'blocked' : 'normal'),
                        'mode_message' => $cptData['mode_message'] ?? $cptData['blocked_message'] ?? null,
                    ]);
                    $created++;
                }
            }

            $summary[] = "CPT Types: {$created} created, {$updated} updated";
        }

        // Import Check Questions
        if (in_array('check_questions', $sections) && !empty($data['check_questions'])) {
            $created = 0;
            $updated = 0;

            foreach ($data['check_questions'] as $qData) {
                if (empty($qData['question_text'])) {
                    continue;
                }

                $existing = CheckQuestion::where('question_text', $qData['question_text'])->first();

                if ($existing) {
                    $existing->update([
                        'options' => $qData['options'] ?? $existing->options,
                        'sort_order' => $qData['sort_order'] ?? $existing->sort_order,
                        'is_active' => $qData['is_active'] ?? $existing->is_active,
                        'is_required' => $qData['is_required'] ?? $existing->is_required,
                    ]);
                    $updated++;
                } else {
                    CheckQuestion::create([
                        'question_text' => $qData['question_text'],
                        'options' => $qData['options'] ?? [],
                        'sort_order' => $qData['sort_order'] ?? 0,
                        'is_active' => $qData['is_active'] ?? true,
                        'is_required' => $qData['is_required'] ?? true,
                    ]);
                    $created++;
                }
            }

            $summary[] = "Check Questions: {$created} created, {$updated} updated";
        }

        // Import Settings
        if (in_array('settings', $sections) && !empty($data['settings'])) {
            $count = 0;

            foreach ($data['settings'] as $key => $value) {
                // Never import sensitive keys
                if (in_array($key, $sensitiveKeys)) {
                    continue;
                }
                // Never import email template keys via settings
                if (str_starts_with($key, 'email_')) {
                    continue;
                }
                Setting::set($key, $value);
                $count++;
            }

            $summary[] = "Settings: {$count} imported";
        }

        // Import Email Templates
        if (in_array('email_templates', $sections) && !empty($data['email_templates'])) {
            $validKeys = array_keys(config('email-templates'));
            $count = 0;

            foreach ($data['email_templates'] as $key => $tplData) {
                if (!in_array($key, $validKeys)) {
                    continue;
                }

                if (!empty($tplData['subject'])) {
                    Setting::set("email_{$key}_subject", $tplData['subject']);
                }
                if (!empty($tplData['body'])) {
                    Setting::set("email_{$key}_body", $tplData['body']);
                }
                $count++;
            }

            $summary[] = "Email Templates: {$count} imported";
        }

        // Import Tags
        if (in_array('tags', $sections) && !empty($data['tags'])) {
            $created = 0;
            $updated = 0;

            foreach ($data['tags'] as $tagData) {
                if (empty($tagData['name'])) {
                    continue;
                }

                $existing = Tag::where('name', $tagData['name'])->first();

                if ($existing) {
                    $existing->update([
                        'colour' => $tagData['colour'] ?? $existing->colour,
                    ]);
                    $updated++;
                } else {
                    Tag::create([
                        'name' => $tagData['name'],
                        'colour' => $tagData['colour'] ?? '#6B7280',
                    ]);
                    $created++;
                }
            }

            $summary[] = "Tags: {$created} created, {$updated} updated";
        }

        if (empty($summary)) {
            return redirect()->route('admin.settings.config')
                ->with('error', 'Nothing was imported. Please select at least one section and ensure the file contains matching data.');
        }

        AuditService::log(
            action: 'imported',
            description: 'Imported configuration: ' . implode('; ', $summary),
        );

        return redirect()->route('admin.settings.config')
            ->with('success', 'Configuration imported successfully. ' . implode('. ', $summary) . '.');
    }
}
