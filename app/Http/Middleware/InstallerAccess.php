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

        // If .env exists and has a real APP_KEY (not the bootstrap placeholder), already configured
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $envContents = file_get_contents($envPath);
            $hasKey = preg_match('/^APP_KEY=base64:.+$/m', $envContents);
            $isBootstrap = str_contains($envContents, 'C0jQeZJHEtJ1EA6Qe1cT/pSPqzsEu90PrwAzvYmJZW8=');
            if ($hasKey && !$isBootstrap) {
                abort(404);
            }
        }

        return $next($request);
    }
}
