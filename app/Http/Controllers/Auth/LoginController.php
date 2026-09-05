<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        $entraEnabled = (bool) Setting::get('entra_enabled');

        return view('auth.login', compact('entraEnabled'));
    }

    public function login(Request $request)
    {
        // Signing in on top of an existing session would carry that session's
        // state into a different identity. Log out first.
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Whatever this session already held belongs to whoever was here before.
        $request->session()->forget('mfa_verified_user_id');

        if (Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            // An account that signs in with Microsoft has exactly one way in.
            // Checked after the password so that a wrong password gives the same
            // answer for every account, and this message only ever reaches the
            // person who could have signed in anyway.
            if (Auth::user()->usesSso()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'This account signs in with Microsoft. Use the Microsoft button above.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            AuditService::log(
                action: 'login',
                model: Auth::user(),
                description: 'Successful login: ' . Auth::user()->email,
            );

            return redirect()->intended(route('admin.dashboard'));
        }

        AuditService::log(
            action: 'login_failed',
            description: 'Failed login attempt for: ' . $request->email,
            newValues: ['email' => $request->email],
        );

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
