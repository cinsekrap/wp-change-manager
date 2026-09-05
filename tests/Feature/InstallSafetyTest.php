<?php

namespace Tests\Feature;

use App\Http\Middleware\InstallerAccess;
use App\Models\User;
use Tests\TestCase;

/**
 * The installer creates a super admin without anyone signing in, so what tells it
 * the site is already set up has to be right. It was one file, and a site that
 * lost it kept serving normally with the installer open behind it.
 */
class InstallSafetyTest extends TestCase
{
    public function test_the_installer_will_not_run_against_a_site_that_has_users(): void
    {
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->withoutMiddleware(InstallerAccess::class)->postJson('/install/admin', [
            'name' => 'New Admin',
            'email' => 'new@example.test',
            'password' => 'Correct-Horse-9',
            'password_confirmation' => 'Correct-Horse-9',
        ])->assertStatus(409);

        $this->assertSame(1, User::count());
    }

    public function test_the_refusal_says_what_to_actually_do(): void
    {
        User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        // A missing lock file is the cause; installing again is not the fix.
        $this->withoutMiddleware(InstallerAccess::class)->postJson('/install/admin', [
            'name' => 'New Admin',
            'email' => 'new@example.test',
            'password' => 'Correct-Horse-9',
            'password_confirmation' => 'Correct-Horse-9',
        ])->assertJsonFragment(['message' => 'This site is already set up. If you are seeing the installer, storage/installed.lock is missing — restore it rather than installing again.']);
    }

    public function test_no_application_key_is_committed(): void
    {
        $template = file_get_contents(base_path('.env.install'));

        // A key in the repository signs this installation's URLs and encrypts its
        // cookies while being readable by anyone with the source.
        $this->assertMatchesRegularExpression('/^APP_KEY=\s*$/m', $template,
            '.env.install carries an application key.');
        $this->assertStringNotContainsString('APP_KEY=base64:', $template);
    }

    public function test_the_bootstrap_template_does_not_turn_debug_on(): void
    {
        $this->assertMatchesRegularExpression(
            '/^APP_DEBUG=false$/m',
            file_get_contents(base_path('.env.install'))
        );
    }

    public function test_the_bootstrap_writes_a_key_of_its_own(): void
    {
        $index = file_get_contents(base_path('public/index.php'));

        // Copying the template verbatim would install a key that is not a secret.
        $this->assertStringNotContainsString("copy(__DIR__.'/../.env.install'", $index);
        $this->assertStringContainsString('random_bytes(32)', $index);
    }

    public function test_no_key_literal_survives_anywhere_in_the_source(): void
    {
        $offending = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('app'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            if (preg_match('/[A-Za-z0-9+\/]{43}=/', file_get_contents($file->getPathname()))) {
                $offending[] = str_replace(base_path().'/', '', $file->getPathname());
            }
        }

        // Removing the file is not enough if the value is also inlined somewhere.
        $this->assertSame([], $offending,
            'A base64 key-shaped literal is still in the source: '.implode(', ', $offending));
    }

    public function test_the_private_disk_publishes_no_routes_of_its_own(): void
    {
        $this->assertFalse(config('filesystems.disks.local.serve'));

        $names = collect(app('router')->getRoutes())->map->getName()->filter()->all();
        $this->assertNotContains('storage.local', $names);
        $this->assertNotContains('storage.local.upload', $names);
    }
}
