<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallerAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only the lock file determines if install is complete
        // The lock file is created at the very last step of the installer
        if (file_exists(storage_path('installed.lock'))) {
            abort(404);
        }

        return $next($request);
    }
}
