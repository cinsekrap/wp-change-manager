<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return $next($request);
        }

        // SSO carries its own second factor, so a session that signed in that way
        // needs no challenge here. Keyed on how this session authenticated rather
        // than on the account, so the skip cannot outlive the sign-in that earned it.
        if ($request->session()->get('auth_via') === 'microsoft') {
            return $next($request);
        }

        // MFA not yet set up — force the user to set it up
        if (! $user->mfa_confirmed_at) {
            return redirect()->route('mfa.setup');
        }

        // MFA is set up but not verified this session, or verified by whoever was
        // signed in before. A challenge is passed by a person, so the session has
        // to name them — a flag that only says "somebody passed" is satisfied by
        // anyone the session later becomes.
        if ((int) $request->session()->get('mfa_verified_user_id') !== (int) $user->id) {
            return redirect()->route('mfa.challenge');
        }

        return $next($request);
    }
}
