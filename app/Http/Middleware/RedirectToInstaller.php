<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToInstaller
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if already on installer routes
        if ($request->is('install') || $request->is('install/*')) {
            return $next($request);
        }

        // If lock file exists, app is installed — proceed normally
        if (file_exists(storage_path('installed.lock'))) {
            return $next($request);
        }

        // Not installed — redirect to the installer.
        //
        // Deliberately no secondary signal. Recognising a shipped key was the old
        // one, and it meant a site whose lock file had gone missing kept serving
        // normally while the installer stood open behind it. A missing lock file
        // is now loud: the installer itself refuses to run once there are users.
        return redirect('/install');
    }
}
