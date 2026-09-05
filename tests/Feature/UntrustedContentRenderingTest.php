<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\Setting;
use App\Models\Site;
use App\Support\SafeUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Content this app renders back to an admin does not all come from an admin. A
 * page address arrives from the public form, and release notes arrive from
 * outside the app entirely — so neither may decide what a link does or emit its
 * own markup.
 */
class UntrustedContentRenderingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function site(): Site
    {
        return Site::firstOrCreate(['domain' => 'hcrg.test'], ['name' => 'HCRG', 'is_active' => true]);
    }

    public function test_safe_url_accepts_web_addresses_and_relative_paths(): void
    {
        foreach (['https://hcrg.test/a-page', 'http://hcrg.test', '/a-page', 'new-content'] as $url) {
            $this->assertSame($url, SafeUrl::for($url), "Rejected a legitimate address: {$url}");
        }
    }

    public function test_safe_url_refuses_anything_a_browser_would_execute(): void
    {
        $refused = [
            'javascript:alert(1)',
            'JavaScript:alert(1)',
            "java\nscript:alert(1)",   // browsers strip the control character first
            "\tjavascript:alert(1)",
            'data:text/html;base64,PHNjcmlwdD4=',
            'vbscript:msgbox(1)',
            '//evil.example/x',        // protocol-relative: leaves the site silently
        ];

        foreach ($refused as $url) {
            $this->assertNull(SafeUrl::for($url), 'Allowed through: '.addcslashes($url, "\n\t"));
        }
    }

    public function test_the_public_form_will_not_store_a_page_address_that_is_not_one(): void
    {
        $this->postJson('/submit', [
            'site_id' => $this->site()->id,
            'page_url' => 'javascript:fetch("//evil.example")',
            'page_title' => 'Health Visiting',
            'cpt_slug' => 'pages',
            'request_type' => 'change',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'items' => [['action_type' => 'add', 'description' => 'Add a phone number.']],
        ])->assertStatus(422)->assertJsonValidationErrors('page_url');

        $this->assertSame(0, ChangeRequest::count());
    }

    public function test_a_page_address_already_stored_is_still_not_linked_to(): void
    {
        // Rows that predate the validation, and the admin-set published address.
        $request = ChangeRequest::create([
            'reference' => 'WCR-XSS-001',
            'site_id' => $this->site()->id,
            'page_url' => 'javascript:alert(document.domain)',
            'page_title' => 'Health Visiting',
            'cpt_slug' => 'pages',
            'status' => 'requested',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
        ]);

        $this->loginAsAdmin();

        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertDontSee('href="javascript:', false)
            // The title still shows; only the link is defused.
            ->assertSee('Health Visiting');
    }

    public function test_release_notes_cannot_bring_their_own_markup(): void
    {
        Setting::set('release_notes', 'All good. <img src=x onerror="alert(1)"> <script>alert(2)</script>');
        Setting::set('whats_new_seen_1', 'never');

        $this->loginAsAdmin();

        $this->get(route('admin.dashboard'))
            ->assertSuccessful()
            ->assertDontSee('<img src=x', false)
            ->assertDontSee('<script>alert(2)', false);
    }

    /** A real import: the file needs a version, and the request names its sections. */
    private function importing(array $settings): void
    {
        $this->loginAsAdmin();

        $this->post(route('admin.settings.config.import'), [
            'import_sections' => ['settings'],
            'config_file' => UploadedFile::fake()->createWithContent(
                'config.json',
                json_encode(['version' => config('version.current'), 'settings' => $settings])
            ),
        ])->assertRedirect();
    }

    public function test_an_imported_file_cannot_write_settings_that_are_rendered_back(): void
    {
        Setting::set('release_notes', 'Legitimate notes.');

        $this->importing(['release_notes' => '<script>alert(1)</script>']);

        $this->assertSame('Legitimate notes.', Setting::get('release_notes'));
    }

    public function test_an_imported_file_cannot_redirect_authentication_or_mail(): void
    {
        $this->importing([
            'entra_enabled' => '1',
            'entra_tenant_id' => 'attacker-tenant',
            'entra_auto_provision' => '1',
            'mail_host' => 'smtp.evil.example',
            'whats_new_seen_1' => 'x',
        ]);

        foreach (['entra_enabled', 'entra_tenant_id', 'entra_auto_provision', 'mail_host', 'whats_new_seen_1'] as $key) {
            $this->assertNull(Setting::get($key), "'{$key}' was writable by an imported file");
        }
    }

    public function test_an_imported_file_still_carries_the_settings_it_is_for(): void
    {
        $this->importing(['sla_normal' => '48', 'chase_hours' => '72']);

        $this->assertSame('48', Setting::get('sla_normal'));
        $this->assertSame('72', Setting::get('chase_hours'));
    }

    public function test_an_export_carries_no_credential_material(): void
    {
        Setting::set('mail_username', 'smtp-user@hcrg.test');
        Setting::set('mail_host', 'smtp.hcrg.test');
        Setting::set('sla_normal', '48');

        $this->loginAsAdmin();

        $settings = $this->post(route('admin.settings.config.export'), ['sections' => ['settings']])
            ->assertSuccessful()
            ->json('settings') ?? [];

        // Half a credential is still credential material.
        $this->assertArrayNotHasKey('mail_username', $settings);
        $this->assertArrayNotHasKey('mail_host', $settings);
        $this->assertSame('48', $settings['sla_normal'] ?? null);
    }

    public function test_a_response_that_sets_its_own_policy_keeps_it(): void
    {
        $this->loginAsAdmin();

        // The email log sandboxes stored message bodies; the global policy
        // middleware used to overwrite that without any sign it had gone.
        $response = $this->get(route('admin.dashboard'))->assertSuccessful();
        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
    }
}
