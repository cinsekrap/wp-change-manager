<?php

namespace Tests\Feature;

use App\Mail\ContentAwaitingFunding;
use App\Mail\ContentPublished;
use App\Models\ChangeRequest;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContentLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function contentRequest(array $overrides = []): ChangeRequest
    {
        $site = Site::create(['name' => 'HCRG Care Group', 'domain' => 'hcrgcaregroup.com', 'is_active' => true]);

        return ChangeRequest::create(array_merge([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => 'content',
            'site_id' => $site->id,
            'page_url' => 'new-content',
            'cpt_slug' => 'content',
            'status' => 'suggested',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'content_type' => 'service_explainer',
        ], $overrides));
    }

    public function test_moving_to_awaiting_funding_tells_the_suggester_why_it_will_take_time(): void
    {
        $request = $this->contentRequest(['status' => 'scoped']);

        $this->loginAsAdmin();

        $this->patch(route('admin.requests.status', $request), ['status' => 'awaiting_funding'])
            ->assertRedirect();

        Mail::assertSent(ContentAwaitingFunding::class);
    }

    public function test_publishing_tells_the_suggester_where_it_landed(): void
    {
        $request = $this->contentRequest(['status' => 'scheduled']);

        $this->loginAsAdmin();

        $this->patch(route('admin.requests.status', $request), ['status' => 'done'])
            ->assertRedirect();

        Mail::assertSent(ContentPublished::class);
    }

    public function test_a_change_request_still_gets_the_ordinary_status_email(): void
    {
        $request = $this->contentRequest(['request_type' => 'change', 'status' => 'requested']);

        $this->loginAsAdmin();

        $this->patch(route('admin.requests.status', $request), ['status' => 'done'])
            ->assertRedirect();

        Mail::assertNotSent(ContentPublished::class);
    }

    public function test_the_admin_can_record_where_content_went_live(): void
    {
        $request = $this->contentRequest(['status' => 'approved']);
        $other = Site::create(['name' => 'Virgin Care Services', 'domain' => 'virgincare.co.uk', 'is_active' => true]);
        $request->additionalSites()->attach($other->id);

        $this->loginAsAdmin();

        $this->patch(route('admin.requests.published', $request), [
                'published' => [
                    $request->site_id => ['url' => 'https://hcrgcaregroup.com/a', 'title' => 'Page A'],
                    $other->id => ['url' => 'https://virgincare.co.uk/b', 'title' => 'Page B'],
                ],
            ])->assertRedirect();

        $request->refresh();
        $this->assertSame('https://hcrgcaregroup.com/a', $request->published_url);
        $this->assertSame('https://virgincare.co.uk/b', $request->publishedFor($other->id)['published_url']);
    }

    public function test_saving_the_draft_voids_a_bound_approval_through_the_admin_route(): void
    {
        $request = $this->contentRequest(['status' => 'approved', 'draft_content' => 'Approved wording.']);
        $approver = $request->approvers()->create([
            'name' => 'Dr Approver',
            'email' => 'a@example.com',
            'status' => 'approved',
            'responded_at' => now(),
            'approved_content_hash' => $request->draftContentHash(),
            'approved_content_snapshot' => $request->draft_content,
        ]);

        $this->loginAsAdmin();

        $this->patch(route('admin.requests.draft', $request), ['draft_content' => 'Rewritten wording.'])
            ->assertRedirect();

        $this->assertSame('pending', $approver->fresh()->status);
        $this->assertSame('awaiting_approval', $request->fresh()->status);
    }

    public function test_the_draft_route_is_closed_to_change_requests(): void
    {
        $request = $this->contentRequest(['request_type' => 'change', 'status' => 'requested']);

        $this->loginAsAdmin();

        $this->patch(route('admin.requests.draft', $request), ['draft_content' => 'nope'])
            ->assertNotFound();
    }

    public function test_content_statuses_are_offered_only_to_content_requests(): void
    {
        $content = $this->contentRequest();
        $change = $this->contentRequest(['request_type' => 'change', 'status' => 'requested']);

        $this->assertContains('awaiting_approval', $content->statusOptions());
        $this->assertNotContains('awaiting_approval', $change->statusOptions());
    }
}
