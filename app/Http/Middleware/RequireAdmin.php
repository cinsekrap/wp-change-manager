<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->is_admin) {
            if ($request->user()) {
                // Logged in but not an admin — show a clear message
                abort(403, 'Your account does not have admin access. Please contact an administrator.');
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
