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
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
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
