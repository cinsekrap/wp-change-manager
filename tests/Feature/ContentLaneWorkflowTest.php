<?php

namespace Tests\Feature;

use App\Mail\RequestStatusChanged;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\CptType;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The workflow guards that only ever named the change lane's statuses. Every test
 * here covers a path that shipped broken and had no coverage.
 */
class ContentLaneWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function siteWithApprovers(): Site
    {
        CptType::firstOrCreate(['slug' => 'pages'], ['name' => 'Pages', 'request_mode' => 'normal']);

        return Site::create([
            'name' => 'HCRG Care Group',
            'domain' => 'hcrgcaregroup.com',
            'is_active' => true,
            // The condition that made this fire in production and never in tests.
            'default_approvers' => [['name' => 'Dr Helen Johal', 'email' => 'h.johal@example.nhs.uk']],
        ]);
    }

    private function contentPayload(Site $site): array
    {
        return [
            'site_id' => $site->id,
            'page_url' => 'new-content',
            'cpt_slug' => 'content',
            'is_new_page' => true,
            'request_type' => 'content',
            'content_type' => 'service_explainer',
            'content_brief' => [
                'achieve' => 'Stop people ringing to ask what happens first.',
                'audience' => ['patients'],
                'know_or_do' => 'Know what to bring.',
                'already_exists' => 'no',
            ],
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'check_answers' => [],
            // The wizard sends this key with an empty array rather than omitting
            // it. Tests that left it out passed while every real submission was
            // rejected, so the shape here has to match what the browser sends.
            'items' => [],
        ];
    }

    public function test_a_content_suggestion_is_not_auto_referred_even_when_the_site_has_approvers(): void
    {
        $site = $this->siteWithApprovers();

        $this->postJson('/submit', $this->contentPayload($site))->assertSuccessful();

        $request = ChangeRequest::where('request_type', 'content')->firstOrFail();

        // It must stay a suggestion: nothing has been scoped, funded or written.
        $this->assertSame('suggested', $request->status);
        $this->assertCount(0, $request->approvers, 'Approvers were emailed about copy that does not exist yet.');
    }

    public function test_a_change_request_still_auto_refers(): void
    {
        $site = $this->siteWithApprovers();

        $this->postJson('/submit', [
            'site_id' => $site->id,
            'page_url' => '/contact-us',
            'page_title' => 'Contact us',
            'cpt_slug' => 'pages',
            'request_type' => 'change',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'items' => [['action_type' => 'add', 'description' => 'Add a phone number.']],
        ])->assertSuccessful();

        // The guard must not have leaked into the lane it was already right for.
        $this->assertSame('referred', ChangeRequest::where('request_type', 'change')->first()->status);
    }

    private function contentAwaitingApproval(): ChangeRequest
    {
        $site = $this->siteWithApprovers();

        $request = ChangeRequest::create([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => 'content',
            'site_id' => $site->id,
            'page_url' => 'new-content',
            'cpt_slug' => 'content',
            'status' => 'awaiting_approval',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'content_type' => 'service_explainer',
            'draft_content' => 'The copy under review.',
        ]);

        $request->approvers()->create([
            'name' => 'Dr Helen Johal',
            'email' => 'h.johal@example.nhs.uk',
            'status' => 'pending',
            'token' => ChangeRequestApprover::generateToken(),
        ]);

        return $request->fresh();
    }

    public function test_approving_advances_a_content_request_out_of_approval(): void
    {
        $request = $this->contentAwaitingApproval();

        $this->post(route('approval.respond', $request->approvers->first()->token), ['status' => 'approved']);

        // Previously a no-op: the guard only named requires_referral and referred.
        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_rejecting_declines_a_content_request_and_tells_the_requester(): void
    {
        $request = $this->contentAwaitingApproval();

        $this->post(route('approval.respond', $request->approvers->first()->token), [
            'status' => 'rejected',
            'notes' => 'Clinically unsafe as written.',
        ]);

        $request->refresh();

        // Previously swallowed entirely: recorded on the approver row and nowhere else.
        $this->assertSame('declined', $request->status);
        $this->assertNotNull($request->rejection_reason);
        Mail::assertSent(RequestStatusChanged::class);
    }

    public function test_a_decline_records_the_status_it_actually_came_from(): void
    {
        $request = $this->contentAwaitingApproval();

        $this->post(route('approval.respond', $request->approvers->first()->token), [
            'status' => 'rejected',
            'notes' => 'No.',
        ]);

        // Was hardcoded to 'referred', which a content request is never in.
        $this->assertDatabaseHas('change_request_status_log', [
            'change_request_id' => $request->id,
            'old_status' => 'awaiting_approval',
            'new_status' => 'declined',
        ]);
    }

    public function test_adding_an_approver_returns_content_to_its_own_approval_state(): void
    {
        $request = $this->contentAwaitingApproval();
        $request->updateQuietly(['status' => 'approved']);

        $clinical = \App\Models\ClinicalApprover::create([
            'name' => 'Dr Second Opinion', 'email' => 'second@example.nhs.uk', 'is_active' => true,
        ]);

        $this->loginAsAdmin();
        $this->post(route('admin.requests.approvers.add', $request), [
            'clinical_approver_id' => $clinical->id,
        ])->assertRedirect();

        // Not requires_referral, which belongs to the change lane.
        $this->assertSame('awaiting_approval', $request->fresh()->status);
    }

    public function test_the_approver_is_shown_the_copy_they_are_signing_off(): void
    {
        $request = $this->contentAwaitingApproval();

        $this->get(route('approval.show', $request->approvers->first()->token))
            ->assertSuccessful()
            ->assertSee('The copy under review.')
            ->assertSee('The copy you are approving')
            // The change-lane framing must not appear.
            ->assertDontSee('item(s)');
    }

    public function test_an_approver_is_warned_when_there_is_no_copy_yet(): void
    {
        $request = $this->contentAwaitingApproval();
        $request->updateQuietly(['draft_content' => null]);

        $this->get(route('approval.show', $request->approvers->first()->token))
            ->assertSuccessful()
            ->assertSee('No copy has been written yet');
    }

    public function test_an_admin_can_set_the_public_title_that_publishes_a_suggestion(): void
    {
        $request = $this->contentAwaitingApproval();

        $this->assertNull($request->public_title);
        $this->get(route('suggestions'))->assertDontSee($request->reference);

        $this->loginAsAdmin();
        $this->patch(route('admin.requests.public-title', $request), [
            'public_title' => 'What happens at your first appointment',
        ])->assertRedirect();

        // Without a write path the public list could never show anything at all.
        $this->assertSame('What happens at your first appointment', $request->fresh()->public_title);
        $this->get(route('suggestions'))->assertSee('What happens at your first appointment');
    }

    public function test_clearing_the_public_title_withdraws_it_from_the_public_list(): void
    {
        $request = $this->contentAwaitingApproval();
        $request->updateQuietly(['public_title' => 'Published title']);

        $this->loginAsAdmin();
        $this->patch(route('admin.requests.public-title', $request), ['public_title' => '']);

        $this->assertNull($request->fresh()->public_title);
        $this->get(route('suggestions'))->assertDontSee('Published title');
    }

    public function test_the_admin_page_shows_the_brief(): void
    {
        $request = $this->contentAwaitingApproval();
        $request->updateQuietly(['content_brief' => [
            'achieve' => 'A distinctive statement of intent.',
            'audience' => ['patients', 'families'],
            'know_or_do' => 'A distinctive call to action.',
            'already_exists' => 'not_sure',
        ]]);

        $this->loginAsAdmin();

        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertSee('A distinctive statement of intent.')
            ->assertSee('A distinctive call to action.')
            ->assertSee('Patients &amp; service users', false)
            ->assertSee('Not sure')
            // An empty items card is noise on a request that carries a brief.
            ->assertDontSee('Change Items (0)');
    }

    public function test_send_for_approval_is_offered_on_a_content_request(): void
    {
        $request = $this->contentAwaitingApproval();
        $request->updateQuietly(['status' => 'in_progress']);

        $this->loginAsAdmin();

        // Gated on status === 'requested', which content never reaches.
        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertSee(route('admin.requests.send-approval', $request), false);
    }
}
