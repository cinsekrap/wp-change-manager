<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailSettingsController extends Controller
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
}
