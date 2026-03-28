<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isSuperAdmin()) {
            abort(403, 'This area requires super admin access.');
        }

        return $next($request);
    }
}
