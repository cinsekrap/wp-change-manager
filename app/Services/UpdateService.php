<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateService
{
    /**
     * Return the current installed version from config.
     */
    public function getCurrentVersion(): string
    {
        return config('version.current', '0.0.0');
    }

    /**
     * Check GitHub for the latest release. Cached for 1 hour.
     */
    public function checkForUpdates(bool $force = false): array
    {
        $cacheKey = 'app_update_check';

        if ($force) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 3600, function () {
            return $this->fetchLatestRelease();
        });
    }

    /**
     * Fetch the latest release from GitHub API.
     */
    protected function fetchLatestRelease(): array
    {
        $current = $this->getCurrentVersion();
        $repo = config('version.repo');

        $default = [
            'available' => false,
            'current_version' => $current,
            'latest_version' => null,
            'release_name' => null,
            'release_notes' => null,
            'published_at' => null,
            'html_url' => null,
            'checked_at' => now()->toIso8601String(),
        ];

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
                ->get("https://api.github.com/repos/{$repo}/releases/latest");

            if (! $response->successful()) {
                Log::warning('Update check failed: GitHub API returned ' . $response->status());

                return $default;
            }

            $data = $response->json();

            // Strip leading "v" from tag name for comparison
            $latestVersion = ltrim($data['tag_name'] ?? '', 'v');

            if (! $latestVersion) {
                return $default;
            }

            $available = version_compare($latestVersion, $current, '>');

            return [
                'available' => $available,
                'current_version' => $current,
                'latest_version' => $latestVersion,
                'release_name' => $data['name'] ?? $data['tag_name'],
                'release_notes' => $data['body'] ?? '',
                'published_at' => $data['published_at'] ?? null,
                'html_url' => $data['html_url'] ?? null,
                'checked_at' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::warning('Update check failed: ' . $e->getMessage());

            return $default;
        }
    }

    /**
     * Pull latest code, run migrations, and clear caches.
     */
    public function installUpdate(): array
    {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'success' => false,
            'steps' => [],
        ];

        try {
            // 1. Git fetch
            $fetchOutput = shell_exec('git fetch origin main 2>&1');
            $log['steps']['git_fetch'] = $fetchOutput ?: 'git not available';

            // 2. Git pull
            $pullOutput = shell_exec('git pull origin main 2>&1');
            $log['steps']['git_pull'] = $pullOutput ?: 'git not available';

            if ($pullOutput && str_contains($pullOutput, 'fatal')) {
                throw new \RuntimeException('Git pull failed: ' . $pullOutput);
            }

            // 3. Migrate
            try {
                Artisan::call('migrate', ['--force' => true]);
                $log['steps']['migrate'] = trim(Artisan::output()) ?: 'Nothing to migrate.';
            } catch (\Exception $e) {
                $log['steps']['migrate'] = 'Error: ' . $e->getMessage();
            }

            // 4. Clear view cache
            try {
                Artisan::call('view:clear');
                $log['steps']['view_clear'] = trim(Artisan::output());
            } catch (\Exception $e) {
                $log['steps']['view_clear'] = 'Error: ' . $e->getMessage();
            }

            // 5. Clear config cache
            try {
                Artisan::call('config:clear');
                $log['steps']['config_clear'] = trim(Artisan::output());
            } catch (\Exception $e) {
                $log['steps']['config_clear'] = 'Error: ' . $e->getMessage();
            }

            $log['success'] = true;

            // Clear the update cache so the next check reflects new version
            Cache::forget('app_update_check');

            Log::info('App update installed successfully', $log);
        } catch (\Exception $e) {
            $log['error'] = $e->getMessage();
            Log::error('App update failed', $log);
        }

        // Append to deploy log for history
        file_put_contents(
            storage_path('logs/deploy.log'),
            json_encode($log) . "\n",
            FILE_APPEND
        );

        return $log;
    }
}
