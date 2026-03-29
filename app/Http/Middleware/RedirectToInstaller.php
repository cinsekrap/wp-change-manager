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

        // Check if .env exists and has a valid APP_KEY
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContents = file_get_contents($envPath);
            if (preg_match('/^APP_KEY=base64:.+$/m', $envContents)) {
                return $next($request);
            }
        }

        // Not installed — redirect to installer
        return redirect('/install');
    }
}
