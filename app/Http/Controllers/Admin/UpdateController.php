<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UpdateController extends Controller
{
    public function __construct(
        protected UpdateService $updateService,
    ) {}

    /**
     * GET /admin/settings/updates
     */
    public function index()
    {
        $update = $this->updateService->checkForUpdates();
        $currentVersion = $this->updateService->getCurrentVersion();

        // Read last deploy log entry if it exists
        $lastDeploy = null;
        $deployLogPath = storage_path('logs/deploy.log');
        if (file_exists($deployLogPath)) {
            $lines = array_filter(explode("\n", file_get_contents($deployLogPath)));
            if (! empty($lines)) {
                $lastDeploy = json_decode(end($lines), true);
            }
        }

        return view('admin.settings.updates', compact('update', 'currentVersion', 'lastDeploy'));
    }

    /**
     * POST /admin/settings/updates/check — force-refresh update check.
     */
    public function check(Request $request)
    {
        $update = $this->updateService->checkForUpdates(force: true);

        if ($update['available']) {
            return redirect()->route('admin.settings.updates')
                ->with('success', "Update available: v{$update['latest_version']}");
        }

        return redirect()->route('admin.settings.updates')
            ->with('success', 'You are running the latest version.');
    }

    /**
     * POST /admin/settings/updates/install — pull and migrate.
     */
    public function install(Request $request)
    {
        $result = $this->updateService->installUpdate();

        Log::info('Admin-initiated update', [
            'user' => auth()->user()->name,
            'result' => $result['success'] ? 'success' : 'failed',
        ]);

        if ($result['success']) {
            return redirect()->route('admin.settings.updates')
                ->with('success', 'Update installed successfully. Please verify everything is working.');
        }

        return redirect()->route('admin.settings.updates')
            ->with('error', 'Update failed: ' . ($result['error'] ?? 'Unknown error. Check logs for details.'));
    }

    /**
     * PUT /admin/settings/github-token — save GitHub API token.
     */
    public function updateGithubToken(Request $request)
    {
        $token = $request->input('github_token');

        if ($token) {
            \App\Models\Setting::set('github_token', $token);
        }

        return redirect()->route('admin.settings.updates')
            ->with('success', 'GitHub token saved.');
    }
}
