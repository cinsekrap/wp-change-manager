<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckQuestion;
use App\Models\CptType;
use App\Models\Setting;

use App\Services\AuditService;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Show the configuration import/export page.
     */
    public function index()
    {
        $counts = [
            'cpt_types' => CptType::count(),
            'check_questions' => CheckQuestion::count(),
        ];

        return view('admin.settings.config', compact('counts'));
    }

    /**
     * Export configuration as a JSON file download.
     */
    public function export(Request $request)
    {
        $sections = $request->input('sections', []);

        $data = [
            'exported_at' => now()->toIso8601String(),
            'version' => config('version.current'),
        ];

        // Sensitive keys that must never be exported
        $sensitiveKeys = ['mail_password', 'entra_client_secret', 'github_token'];

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
    public function import(Request $request)
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

        $sensitiveKeys = ['mail_password', 'entra_client_secret', 'github_token'];
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
