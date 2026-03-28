<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;

class EntraSettingsController extends Controller
{
    public function edit()
    {
        $settings = Setting::getMany([
            'entra_enabled',
            'entra_tenant_id',
            'entra_client_id',
            'entra_client_secret',
            'entra_auto_provision',
        ]);

        return view('admin.settings.entra', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'entra_enabled'        => 'nullable|boolean',
            'entra_tenant_id'      => 'nullable|string|max:255',
            'entra_client_id'      => 'nullable|string|max:255',
            'entra_client_secret'  => 'nullable|string|max:500',
            'entra_auto_provision' => 'nullable|boolean',
        ]);

        Setting::set('entra_enabled', $request->boolean('entra_enabled') ? '1' : '0');
        Setting::set('entra_auto_provision', $request->boolean('entra_auto_provision') ? '1' : '0');

        if (! empty($validated['entra_tenant_id'])) {
            Setting::set('entra_tenant_id', $validated['entra_tenant_id']);
        }

        if (! empty($validated['entra_client_id'])) {
            Setting::set('entra_client_id', $validated['entra_client_id']);
        }

        // Don't overwrite client secret if the field was left empty
        if (! empty($validated['entra_client_secret'])) {
            Setting::set('entra_client_secret', $validated['entra_client_secret']);
        }

        AuditService::log(
            action: 'updated',
            description: 'Updated SSO settings',
            newValues: [
                'entra_enabled' => $request->boolean('entra_enabled'),
                'entra_auto_provision' => $request->boolean('entra_auto_provision'),
            ],
        );

        return redirect()->route('admin.settings.entra')->with('success', 'SSO settings saved.');
    }
}
