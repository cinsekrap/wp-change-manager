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

        // A signed-in user is linking their own account rather than signing in.
        session(['sso_intent' => Auth::check() ? 'link' : 'login']);

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

        $intent = session()->pull('sso_intent', 'login');

        if ($intent === 'link' && Auth::check()) {
            return $this->linkCurrentUser($microsoftUser);
        }

        // Look up by provider + provider_id first
        $user = User::where('provider', 'microsoft')
            ->where('provider_id', $microsoftUser->getId())
            ->first();

        // Deliberately NOT matched by email. Linking on a matching address means
        // whoever can present that address as their Microsoft identity inherits
        // the account, and linking is one-way from the account holder's point of
        // view — password sign-in stops working for a linked account. Existing
        // users link their own account from their profile page, while signed in.
        if (! $user && User::where('email', $microsoftUser->getEmail())->exists()) {
            return redirect()->route('login')->withErrors([
                'email' => 'There is already an account with this email address. Sign in with your password first, then link Microsoft sign-in from your profile.',
            ]);
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

        // Records how this session authenticated, which is what decides whether
        // it still needs a challenge of its own.
        request()->session()->put('auth_via', 'microsoft');

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

    /**
     * Link the signed-in user's own account to the Microsoft identity they just
     * proved they hold.
     */
    private function linkCurrentUser($microsoftUser)
    {
        $user = Auth::user();

        $takenBy = User::where('provider', 'microsoft')
            ->where('provider_id', $microsoftUser->getId())
            ->where('id', '!=', $user->id)
            ->exists();

        if ($takenBy) {
            return redirect()->route('admin.password.edit')
                ->with('error', 'That Microsoft account is already linked to another user.');
        }

        // Linking removes this account's password sign-in, so it must not take
        // the last super admin who could still get in without the provider.
        if (User::breakGlass()->where('id', '!=', $user->id)->doesntExist()) {
            return redirect()->route('admin.password.edit')
                ->with('error', 'You are the last super admin who can sign in with a password. Link a second super admin first, so nobody is locked out if Microsoft sign-in is unavailable.');
        }

        $user->update([
            'provider'    => 'microsoft',
            'provider_id' => $microsoftUser->getId(),
        ]);

        session(['auth_via' => 'microsoft']);

        AuditService::log(
            action: 'sso_linked',
            model: $user,
            description: "Linked Microsoft sign-in: {$user->email}",
        );

        return redirect()->route('admin.password.edit')
            ->with('success', 'Microsoft sign-in is now linked to your account. You will sign in with Microsoft from now on.');
    }
}
