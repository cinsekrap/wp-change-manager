<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DeployController extends Controller
{
    public function __invoke(Request $request, string $token): JsonResponse
    {
        $expected = config('app.deploy_token');

        if (!$expected || !hash_equals($expected, $token)) {
            abort(403);
        }

        if ($expected === 'change-me-to-a-long-random-string') {
            abort(503, 'Deploy token has not been changed from the default. Update DEPLOY_TOKEN in .env.');
        }

        $log = ['timestamp' => date('Y-m-d H:i:s'), 'ip' => $request->ip()];

        // Try git pull
        $gitResult = shell_exec('git pull origin main 2>&1');
        $log['git'] = $gitResult ?: 'git not available';

        // Try migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
            $log['migrate'] = trim(Artisan::output()) ?: 'Nothing to migrate.';
        } catch (\Exception $e) {
            $log['migrate'] = 'Error: ' . $e->getMessage();
        }

        file_put_contents(storage_path('logs/deploy.log'), json_encode($log) . "\n", FILE_APPEND);

        return response()->json(['status' => 'ok']);
    }
}
