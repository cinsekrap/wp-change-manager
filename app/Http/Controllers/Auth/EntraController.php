<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class EntraController extends Controller
{
    /**
     * Redirect to Microsoft for authentication.
     */
    public function redirect()
    {
        abort_unless((bool) Setting::get('entra_enabled'), 404);

        return Socialite::driver('microsoft')->redirect();
    }

    /**
     * Handle the callback from Microsoft.
     */
    public function callback()
    {
        abort_unless((bool) Setting::get('entra_enabled'), 404);

        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Microsoft authentication failed. Please try again.',
            ]);
        }

        // Look up by provider + provider_id first
        $user = User::where('provider', 'microsoft')
            ->where('provider_id', $microsoftUser->getId())
            ->first();

        // If not found, try matching by email
        if (! $user) {
            $user = User::where('email', $microsoftUser->getEmail())->first();

            if ($user) {
                // Link the existing account to Microsoft
                $user->update([
                    'provider'    => 'microsoft',
                    'provider_id' => $microsoftUser->getId(),
                ]);
            }
        }

        // If still not found, check auto-provisioning
        if (! $user) {
            if ((bool) Setting::get('entra_auto_provision')) {
                $user = User::create([
                    'name'        => $microsoftUser->getName(),
                    'email'       => $microsoftUser->getEmail(),
                    'password'    => Str::random(64),
                    'provider'    => 'microsoft',
                    'provider_id' => $microsoftUser->getId(),
                    'is_active'   => true,
                ]);

                AuditService::log(
                    action: 'auto_provisioned',
                    model: $user,
                    description: "User auto-provisioned via SSO: {$user->name} ({$user->email})",
                    newValues: ['name' => $user->name, 'email' => $user->email],
                );
            } else {
                return redirect()->route('login')->withErrors([
                    'email' => 'No account found for this Microsoft account. Please contact an administrator.',
                ]);
            }
        }

        // Ensure user is active
        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact an administrator.',
            ]);
        }

        Auth::login($user);
        request()->session()->regenerate();

        AuditService::log(
            action: 'sso_login',
            model: $user,
            description: "SSO login: {$user->email}",
        );

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Non-admin SSO users: redirect to the public wizard with a message
        return redirect()->route('wizard')->with('success', 'Signed in as ' . $user->name . '. Contact an administrator to request admin access.');
    }
}
