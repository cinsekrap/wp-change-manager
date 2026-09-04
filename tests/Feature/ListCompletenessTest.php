<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\Setting;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Guards against the failure this codebase keeps repeating: a list written out by
 * hand that silently falls behind the source of truth it duplicates.
 *
 * Every assertion here corresponds to a bug that actually shipped — raw
 * "Awaiting_funding" labels, a preview panel twelve templates behind, a tracking
 * page whose explainer card vanished for the whole content lane.
 */
class ListCompletenessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_every_status_has_a_label_and_a_colour(): void
    {
        foreach (ChangeRequest::STATUSES as $status) {
            $this->assertArrayHasKey($status, ChangeRequest::STATUS_COLORS, "No badge colour for '{$status}'");
            // A slug reaching the UI is the bug; a single-word status like "Done"
            // is legitimately its own label.
            $this->assertStringNotContainsString('_', ChangeRequest::statusLabel($status),
                "'{$status}' renders with an underscore");
        }
    }

    public function test_the_dashboard_buckets_account_for_every_live_status(): void
    {
        // Statuses the tiles deliberately exclude.
        $terminal = ChangeRequest::TERMINAL_STATUSES;

        $counted = array_merge(
            ['requested', 'requires_referral', 'suggested'],
            ['referred', 'approved', 'training', 'trained', 'scheduled',
             'scoped', 'awaiting_funding', 'in_progress', 'awaiting_approval',
             'on_hold', 'awaiting_user'],
        );

        $uncounted = array_diff(ChangeRequest::STATUSES, $counted, $terminal);

        $this->assertSame([], array_values($uncounted),
            'These statuses appear in no dashboard tile, so live work is invisible: '.implode(', ', $uncounted));
    }

    public function test_the_public_tracking_page_explains_every_status(): void
    {
        $site = Site::create(['name' => 'HCRG', 'domain' => 'hcrg.test', 'is_active' => true]);
        $missing = [];

        foreach (ChangeRequest::STATUSES as $status) {
            $request = ChangeRequest::create([
                'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(4))),
                'request_type' => in_array($status, ChangeRequest::CONTENT_ONLY_STATUSES) ? 'content' : 'change',
                'site_id' => $site->id,
                'page_url' => '/a-page',
                'page_title' => 'A page',
                'cpt_slug' => 'pages',
                'status' => $status,
                'requester_name' => 'Jane Doe',
                'requester_email' => 'jane@example.com',
            ]);

            $html = $this->get(\App\Http\Controllers\PublicSite\TrackingController::signedUrl($request))
                ->assertSuccessful()
                ->getContent();

            // The explainer card is behind @if($explainer); with no entry it vanishes
            // and the requester gets a bare badge with no explanation.
            if (!str_contains($html, 'data-status-explainer')) {
                $missing[] = $status;
            }
        }

        $this->assertSame([], $missing, 'No tracking explainer for: '.implode(', ', $missing));
    }

    public function test_the_bulk_menu_offers_only_statuses_the_endpoint_accepts(): void
    {
        $this->loginAsAdmin();

        $html = $this->get(route('admin.requests.index'))->assertSuccessful()->getContent();

        foreach (ChangeRequest::SLA_PAUSED_STATUSES as $status) {
            // These need a reason a bulk action cannot carry, so the controller
            // 422s them — offering them in the menu is a dead end.
            $this->assertStringNotContainsString(
                "bulkChangeStatus('{$status}'",
                $html,
                "The bulk menu offers '{$status}', which BulkActionController rejects"
            );
        }
    }

    public function test_the_bulk_menu_renders_canonical_labels(): void
    {
        $this->loginAsAdmin();

        $html = $this->get(route('admin.requests.index'))->assertSuccessful()->getContent();

        foreach (array_diff(ChangeRequest::STATUSES, ChangeRequest::SLA_PAUSED_STATUSES) as $status) {
            $this->assertStringContainsString(
                e(ChangeRequest::statusLabel($status)),
                $html,
                "The bulk menu does not render the canonical label for '{$status}'"
            );
        }
    }

    public function test_every_wizard_upload_accept_list_matches_the_server(): void
    {
        $allowed = (new \ReflectionClass(\App\Http\Controllers\Api\UploadController::class))
            ->getDefaultProperties()['allowedMimes'];

        // Extension → MIME, for the extensions the wizard offers.
        $map = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
            'webp' => 'image/webp', 'pdf' => 'application/pdf', 'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain', 'csv' => 'text/csv',
            'mp4' => 'video/mp4', 'mov' => 'video/quicktime', 'webm' => 'video/webm', 'avi' => 'video/x-msvideo',
        ];

        $offending = [];
        foreach (glob(resource_path('views/public/partials/wizard/*.blade.php')) as $file) {
            preg_match_all('/accept="([^"]+)"/', file_get_contents($file), $matches);
            foreach ($matches[1] as $accept) {
                foreach (explode(',', $accept) as $ext) {
                    $ext = ltrim(trim($ext), '.');
                    if ($ext && (!isset($map[$ext]) || !in_array($map[$ext], $allowed))) {
                        $offending[] = basename($file).": .{$ext}";
                    }
                }
            }
        }

        // Offering an extension the server rejects means the picker accepts the
        // file and the upload then dies with a raw validator message.
        $this->assertSame([], array_unique($offending),
            'Accept lists offer types the server rejects: '.implode(', ', array_unique($offending)));
    }

    public function test_the_sensitive_key_list_has_exactly_one_definition(): void
    {
        $config = file_get_contents(app_path('Http/Controllers/Admin/ConfigController.php'));

        // The config export decrypts on read, so a hand-copy that misses a secret
        // writes it in plaintext into a downloadable file.
        $this->assertStringNotContainsString("'mail_password',", $config,
            'ConfigController hand-copies the encrypted-key list instead of deriving it from Setting.');
        $this->assertContains('mail_password', Setting::encryptedKeys());
    }
}
