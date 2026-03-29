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
            $headers = [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => config('app.name', 'ACME-Change'),
            ];

            // Use GitHub token if configured (raises rate limit from 60 to 5,000/hr)
            $token = \App\Models\Setting::get('github_token');
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $response = Http::timeout(10)
                ->withHeaders($headers)
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
     * Download latest release zip from GitHub, extract, run migrations, clear caches.
     */
    public function installUpdate(): array
    {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'success' => false,
            'steps' => [],
        ];

        try {
            $repo = config('version.repo');

            // 1. Download release zip from GitHub
            $zipUrl = "https://github.com/{$repo}/archive/refs/heads/main.zip";
            $tempZip = storage_path('app/update.zip');
            $tempDir = storage_path('app/update-extract');

            $response = Http::timeout(60)->get($zipUrl);
            if (!$response->successful()) {
                throw new \RuntimeException('Failed to download update: HTTP ' . $response->status());
            }
            file_put_contents($tempZip, $response->body());
            $log['steps']['download'] = 'Downloaded ' . round(strlen($response->body()) / 1024 / 1024, 1) . 'MB';

            // 2. Extract zip
            $zip = new \ZipArchive();
            if ($zip->open($tempZip) !== true) {
                throw new \RuntimeException('Failed to open zip file.');
            }
            // Clean extract dir
            if (is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            $zip->extractTo($tempDir);
            $zip->close();
            $log['steps']['extract'] = 'Extracted successfully.';

            // 3. Find the extracted directory (GitHub zips have a top-level folder like repo-main/)
            $extractedDirs = glob($tempDir . '/*', GLOB_ONLYDIR);
            if (empty($extractedDirs)) {
                throw new \RuntimeException('No directory found in extracted zip.');
            }
            $sourceDir = $extractedDirs[0];

            // 4. Copy files to app root, preserving .env, storage, and installed.lock
            $appRoot = base_path();
            $protectedPaths = ['.env', '.env.install', 'storage', '.git'];
            $this->copyDirectory($sourceDir, $appRoot, $protectedPaths);
            $log['steps']['copy'] = 'Files updated.';

            // 5. Clean up temp files
            @unlink($tempZip);
            $this->deleteDirectory($tempDir);
            $log['steps']['cleanup'] = 'Temp files removed.';

            // 6. Migrate
            try {
                Artisan::call('migrate', ['--force' => true]);
                $log['steps']['migrate'] = trim(Artisan::output()) ?: 'Nothing to migrate.';
            } catch (\Exception $e) {
                $log['steps']['migrate'] = 'Error: ' . $e->getMessage();
            }

            // 7. Clear caches
            try {
                Artisan::call('view:clear');
                $log['steps']['view_clear'] = trim(Artisan::output());
            } catch (\Exception $e) {
                $log['steps']['view_clear'] = 'Error: ' . $e->getMessage();
            }

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

            // Clean up on failure
            @unlink($tempZip ?? '');
            if (isset($tempDir) && is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
        }

        // Append to deploy log for history
        file_put_contents(
            storage_path('logs/deploy.log'),
            json_encode($log) . "\n",
            FILE_APPEND
        );

        return $log;
    }

    /**
     * Recursively copy directory, skipping protected paths.
     */
    protected function copyDirectory(string $source, string $dest, array $protectedPaths = []): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($source) + 1);

            // Skip protected paths
            $skip = false;
            foreach ($protectedPaths as $protected) {
                if ($relativePath === $protected || str_starts_with($relativePath, $protected . '/') || str_starts_with($relativePath, $protected . DIRECTORY_SEPARATOR)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            $targetPath = $dest . '/' . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0775, true);
                }
            } else {
                // Ensure parent directory exists
                $parentDir = dirname($targetPath);
                if (!is_dir($parentDir)) {
                    mkdir($parentDir, 0775, true);
                }
                copy($item->getPathname(), $targetPath);
            }
        }
    }

    /**
     * Recursively delete a directory.
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }
}
