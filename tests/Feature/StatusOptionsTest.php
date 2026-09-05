<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Each lane is offered its own statuses and no one else's. statusOptions() is
 * the single definition — the sidebar dropdown, the single-request endpoint and
 * the bulk endpoint all read it — so this covers every one of them at once.
 */
class StatusOptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function request(string $type, string $status): ChangeRequest
    {
        $site = Site::firstOrCreate(['domain' => 'hcrg.test'], ['name' => 'HCRG', 'is_active' => true]);

        return ChangeRequest::create([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => $type,
            'site_id' => $site->id,
            'page_url' => $type === 'content' ? 'new-content' : '/a-page',
            'page_title' => 'A page',
            'cpt_slug' => $type === 'content' ? 'content' : 'pages',
            'status' => $status,
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'access_recipient_name' => $type === 'access' ? 'Jane Doe' : null,
            'access_recipient_email' => $type === 'access' ? 'jane@example.com' : null,
        ]);
    }

    public function test_content_is_not_offered_the_change_lanes_intake_and_referral(): void
    {
        $options = $this->request('content', 'in_progress')->statusOptions();

        // Content is suggested, not requested; it goes to a named clinician for
        // awaiting_approval, never to a site's approvers via referred.
        foreach (ChangeRequest::REFERRAL_STATUSES as $status) {
            $this->assertNotContains($status, $options, "Content is offered '{$status}'");
        }
    }

    public function test_content_keeps_its_own_lifecycle_and_the_shared_states(): void
    {
        $options = $this->request('content', 'in_progress')->statusOptions();

        foreach (ChangeRequest::CONTENT_ONLY_STATUSES as $status) {
            $this->assertContains($status, $options);
        }
        // Approved and done are shared; scheduled is a deliberate inclusion —
        // content can be held for a publication date.
        foreach (['approved', 'scheduled', 'on_hold', 'awaiting_user', 'done', 'declined', 'cancelled'] as $status) {
            $this->assertContains($status, $options);
        }
        foreach (ChangeRequest::ACCESS_ONLY_STATUSES as $status) {
            $this->assertNotContains($status, $options);
        }
    }

    public function test_change_and_access_still_get_the_referral_path(): void
    {
        foreach (['change', 'access'] as $type) {
            $options = $this->request($type, 'requested')->statusOptions();

            foreach (ChangeRequest::REFERRAL_STATUSES as $status) {
                $this->assertContains($status, $options, "{$type} lost '{$status}'");
            }
            foreach (ChangeRequest::CONTENT_ONLY_STATUSES as $status) {
                $this->assertNotContains($status, $options, "{$type} is offered content's '{$status}'");
            }
        }
    }

    public function test_the_endpoint_refuses_a_status_from_another_lane(): void
    {
        $content = $this->request('content', 'in_progress');

        $this->loginAsAdmin();
        $this->patch(route('admin.requests.status', $content), ['status' => 'referred'])
            ->assertSessionHasErrors('status');

        $this->assertSame('in_progress', $content->fresh()->status);
    }

    public function test_the_bulk_endpoint_skips_a_status_from_another_lane(): void
    {
        $content = $this->request('content', 'in_progress');
        $change = $this->request('change', 'requested');

        $this->loginAsAdmin();
        $this->post(route('admin.requests.bulk.status'), [
            'ids' => [$content->id, $change->id],
            'status' => 'referred',
        ]);

        // The change request takes it; the content request is left alone rather
        // than dragged into a lane it has no path through.
        $this->assertSame('referred', $change->fresh()->status);
        $this->assertSame('in_progress', $content->fresh()->status);
    }

    public function test_the_dropdown_still_shows_where_a_request_actually_is(): void
    {
        // Content left in a change-lane status by the code that used to allow it.
        $stranded = $this->request('content', 'referred');

        $this->assertContains('referred', $stranded->statusOptions(),
            'The dropdown would show a different status as selected, and Update would move it.');

        $this->loginAsAdmin();
        $this->get(route('admin.requests.show', $stranded))
            ->assertSuccessful()
            ->assertSee('value="referred" selected', false);
    }

    public function test_every_status_is_offered_to_at_least_one_lane(): void
    {
        $offered = collect(['change', 'access', 'content'])
            ->flatMap(fn ($type) => $this->request($type, 'done')->statusOptions())
            ->unique();

        // A status no lane offers can never be reached through the UI, which
        // makes it dead weight in STATUSES.
        $this->assertSame([], array_values(array_diff(ChangeRequest::STATUSES, $offered->all())));
    }
}
