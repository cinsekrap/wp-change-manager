<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallerAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // If lock file exists, the app is already installed
        if (file_exists(storage_path('installed.lock'))) {
            abort(404);
        }

        // If .env exists and has a valid APP_KEY, the app is already configured
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContents = file_get_contents($envPath);
            if (preg_match('/^APP_KEY=base64:.+$/m', $envContents)) {
                abort(404);
            }
        }

        return $next($request);
    }
}
