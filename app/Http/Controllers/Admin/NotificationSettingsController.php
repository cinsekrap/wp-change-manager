<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use App\Models\Setting;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    /**
     * Show the notifications settings page (templates hub, SLA, chase reminders).
     */
    public function edit()
    {
        return view('admin.settings.notifications');
    }

    /**
     * Save SLA turnaround times.
     */
    public function updateSla(Request $request)
    {
        $request->validate([
            'sla_low' => 'nullable|integer|min:1',
            'sla_normal' => 'nullable|integer|min:1',
            'sla_high' => 'nullable|integer|min:1',
            'sla_urgent' => 'nullable|integer|min:1',
        ]);

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
     * Save new-request alert settings.
     */
    public function updateNewRequestAlert(Request $request)
    {
        $validated = $request->validate([
            'new_request_alert_email' => 'nullable|email|max:255',
        ]);

        Setting::set('new_request_alert_email', $validated['new_request_alert_email'] ?? '');

        return redirect()->route('admin.settings.notifications')->with('success', 'New request alert settings saved.');
    }
}
