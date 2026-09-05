<?php

namespace Tests\Feature;

use App\Services\UpdateService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The updater writes executable code into the application root, so every step of
 * deciding what to install has to fail closed. The failure that mattered was the
 * quiet one: when the release lookup did not come back, the old code guessed at a
 * download and then reported verification as "skipped", so the integrity check
 * disappeared exactly when something was already wrong.
 */
class UpdateFailClosedTest extends TestCase
{
    private function service(): UpdateService
    {
        return app(UpdateService::class);
    }

    private function install(): array
    {
        return $this->service()->installUpdate();
    }

    /**
     * Refused for the stated reason, and refused BEFORE extraction — a later
     * failure would satisfy "did not install" while proving nothing about the
     * gate under test.
     */
    private function assertRefused(array $log, string $reason, string $because): void
    {
        $this->assertFalse($log['success'], "Installed anyway: {$because}");
        $this->assertStringContainsString(
            $reason,
            strtolower($log['error'] ?? ''),
            "Stopped, but not because {$because} — it failed with: ".($log['error'] ?? 'no error')
        );
        $this->assertArrayNotHasKey('extract', $log['steps'],
            "Reached extraction despite {$because}");
    }

    public function test_a_release_lookup_that_fails_installs_nothing(): void
    {
        Http::fake(['api.github.com/*' => Http::response('', 503)]);

        // A lookup that fails reports no available release, so that guard fires
        // first — either way the point is that nothing is downloaded, and in
        // particular not the tip of the default branch.
        $this->assertRefused($this->install(), 'no update is available', 'the release lookup returned 503');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'archive/refs/heads'));
    }

    public function test_a_release_with_no_checksum_installs_nothing(): void
    {
        Http::fake([
            'api.github.com/*' => Http::response([
                'tag_name' => 'v99.0.0',
                'name' => 'v99.0.0',
                'body' => 'Some notes with no checksum line at all.',
                'zipball_url' => 'https://api.github.com/repos/x/y/zipball/v99.0.0',
                'published_at' => now()->toIso8601String(),
                'html_url' => 'https://example.test',
            ]),
            '*zipball*' => Http::response('not-a-real-zip'),
        ]);

        $this->assertRefused($this->install(), 'no sha-256 checksum', 'the release published no checksum');
    }

    public function test_a_checksum_that_does_not_match_installs_nothing(): void
    {
        Http::fake([
            'api.github.com/*' => Http::response([
                'tag_name' => 'v99.0.0',
                'name' => 'v99.0.0',
                'body' => 'SHA-256: '.str_repeat('a', 64),
                'zipball_url' => 'https://api.github.com/repos/x/y/zipball/v99.0.0',
                'published_at' => now()->toIso8601String(),
                'html_url' => 'https://example.test',
            ]),
            '*zipball*' => Http::response('bytes that hash to something else'),
        ]);

        $this->assertRefused($this->install(), 'mismatch', 'the checksum did not match');
    }

    public function test_nothing_is_installed_when_no_update_is_available(): void
    {
        // The current version, published as the latest release.
        Http::fake([
            'api.github.com/*' => Http::response([
                'tag_name' => 'v'.config('version.current'),
                'name' => 'current',
                'body' => 'SHA-256: '.str_repeat('a', 64),
                'zipball_url' => 'https://api.github.com/repos/x/y/zipball/current',
                'published_at' => now()->toIso8601String(),
                'html_url' => 'https://example.test',
            ]),
        ]);

        $this->assertRefused($this->install(), 'no update is available', 'there was no newer release');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'zipball'));
    }
}
