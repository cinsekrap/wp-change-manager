<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

class MfaController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Show MFA setup page with QR code.
     */
    public function setup(Request $request)
    {
        $user = $request->user();

        // If MFA is already set up and confirmed, redirect to dashboard
        if ($user->hasMfaEnabled()) {
            return redirect()->route('admin.dashboard');
        }

        // Generate a new secret if not already in session
        $secret = $request->session()->get('mfa_setup_secret');
        if (! $secret) {
            $secret = $this->google2fa->generateSecretKey();
            $request->session()->put('mfa_setup_secret', $secret);
        }

        // Generate the OTP provisioning URL
        $otpUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Render QR code as SVG
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($otpUrl);

        return view('auth.mfa-setup', [
            'qrCodeSvg' => $qrCodeSvg,
            'secret' => $secret,
        ]);
    }

    /**
     * Confirm the MFA setup with a valid TOTP code.
     */
    public function confirmSetup(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $secret = $request->session()->get('mfa_setup_secret');

        if (! $secret) {
            return redirect()->route('mfa.setup')->withErrors([
                'code' => 'Session expired. Please try again.',
            ]);
        }

        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (! $valid) {
            return back()->withErrors([
                'code' => 'Invalid code. Please try again.',
            ]);
        }

        // Save the secret and enable MFA
        $request->user()->update([
            'mfa_secret' => $secret,
            'mfa_enabled' => true,
            'mfa_confirmed_at' => now(),
        ]);

        // Mark MFA as verified for this session
        $request->session()->put('mfa_verified', true);
        $request->session()->forget('mfa_setup_secret');

        return redirect()->route('admin.dashboard');
    }

    /**
     * Show the MFA challenge page.
     */
    public function challenge(Request $request)
    {
        $user = $request->user();

        // If user doesn't have MFA set up, redirect to setup
        if (! $user->hasMfaEnabled()) {
            return redirect()->route('mfa.setup');
        }

        // If already verified, go to dashboard
        if ($request->session()->get('mfa_verified')) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.mfa-challenge');
    }

    /**
     * Verify the MFA code.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $user = $request->user();
        $valid = $this->google2fa->verifyKey($user->mfa_secret, $request->code);

        if (! $valid) {
            return back()->withErrors([
                'code' => 'Invalid code. Please try again.',
            ]);
        }

        $request->session()->put('mfa_verified', true);

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Disable MFA (requires current password confirmation).
     */
    public function disable(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
        ]);

        $request->user()->update([
            'mfa_secret' => null,
            'mfa_enabled' => false,
            'mfa_confirmed_at' => null,
        ]);

        return back()->with('success', 'Two-factor authentication has been disabled. You will be asked to set it up again on your next login.');
    }
}
