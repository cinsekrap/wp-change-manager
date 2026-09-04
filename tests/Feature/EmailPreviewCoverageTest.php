<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The preview list on the notifications page is generated from config. That means
 * a template without a working preview becomes a visible broken link rather than
 * something nobody notices — so every template must render.
 */
class EmailPreviewCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_every_configured_template_has_a_working_preview(): void
    {
        $site = Site::create(['name' => 'HCRG Care Group', 'domain' => 'hcrgcaregroup.com', 'is_active' => true]);
        ChangeRequest::create([
            'reference' => 'CR-PREVIEW',
            'request_type' => 'content',
            'site_id' => $site->id,
            'page_url' => 'new-content',
            'cpt_slug' => 'content',
            'status' => 'suggested',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'content_type' => 'service_explainer',
            'public_title' => 'A published title',
        ]);

        $this->loginAsAdmin();

        $broken = [];
        foreach (array_keys(config('email-templates')) as $key) {
            $slug = str_replace('_', '-', $key);
            $status = $this->get(route('admin.settings.mail.preview', $slug))->getStatusCode();
            if ($status !== 200) {
                $broken[] = "{$slug} ({$status})";
            }
        }

        // Report every broken preview at once rather than stopping at the first.
        $this->assertSame([], $broken, 'Templates without a working preview: '.implode(', ', $broken));
    }

    public function test_the_notifications_page_links_every_template(): void
    {
        $this->loginAsAdmin();

        $response = $this->get(route('admin.settings.notifications'))->assertSuccessful();

        // The list is generated, so all 20 appear — it had drifted to 8 when hand-written.
        foreach (config('email-templates') as $key => $tpl) {
            $response->assertSee(route('admin.settings.mail.preview', str_replace('_', '-', $key)), false);
        }
    }
}
