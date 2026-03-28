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

        // SSO users bypass MFA — Microsoft handles their MFA
        if ($user->provider === 'microsoft') {
            return $next($request);
        }

        // MFA not yet set up — force the user to set it up
        if (! $user->mfa_confirmed_at) {
            return redirect()->route('mfa.setup');
        }

        // MFA is set up but not verified this session
        if (! $request->session()->get('mfa_verified')) {
            return redirect()->route('mfa.challenge');
        }

        return $next($request);
    }
}
