<?php

namespace Tests\Feature;

use App\Mail\ContentRevisionNeeded;
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

    private function designerOn(ChangeRequest $request): \App\Models\User
    {
        $designer = \App\Models\User::factory()->create([
            'name' => 'Sam Designer',
            'email' => 'sam@example.com',
        ]);

        $request->updateQuietly(['assigned_to' => $designer->id]);
        $request->refresh();

        return $designer;
    }

    public function test_approving_advances_a_content_request_out_of_approval(): void
    {
        $request = $this->contentAwaitingApproval();

        $this->post(route('approval.respond', $request->approvers->first()->token), ['status' => 'approved']);

        // Previously a no-op: the guard only named requires_referral and referred.
        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_rejecting_content_sends_it_back_to_the_designer_rather_than_declining_it(): void
    {
        $request = $this->contentAwaitingApproval();
        $designer = $this->designerOn($request);

        $this->post(route('approval.respond', $request->approvers->first()->token), [
            'status' => 'rejected',
            'notes' => 'Clinically unsafe as written.',
            'share_details' => '1',
        ]);

        $request->refresh();

        // A clinician saying "not as written" is asking for a rewrite, not for
        // the page to be abandoned. The brief, funding and hours all survive.
        $this->assertSame('in_progress', $request->status);
        $this->assertNull($request->rejection_reason, 'A request still being written must not carry a decline reason.');

        // The designer is the only person who can act on the feedback.
        Mail::assertSent(ContentRevisionNeeded::class, fn ($mail) => $mail->hasTo($designer->email));
        $this->assertStringContainsString('Clinically unsafe as written.', $request->notes->first()->note);
    }

    public function test_the_requester_is_told_the_copy_is_being_revised_not_that_it_was_declined(): void
    {
        $request = $this->contentAwaitingApproval();
        $this->designerOn($request);

        $this->post(route('approval.respond', $request->approvers->first()->token), [
            'status' => 'rejected',
            'notes' => 'Needs a safety-netting line.',
        ]);

        // Whoever asked for it still hears, but the status they are told is the
        // one the request is actually in.
        Mail::assertSent(RequestStatusChanged::class, fn ($mail) => $mail->hasTo('jane@example.com'));
        $this->assertSame('in_progress', $request->fresh()->status);
    }

    public function test_content_the_team_started_itself_still_reaches_the_designer(): void
    {
        $request = $this->contentAwaitingApproval();
        $designer = $this->designerOn($request);

        // Designer-initiated content has nobody to email a status change to.
        $request->updateQuietly(['requester_email' => null, 'requester_name' => null]);

        $this->post(route('approval.respond', $request->fresh()->approvers->first()->token), [
            'status' => 'rejected',
            'notes' => 'Reads as advice rather than information.',
        ]);

        Mail::assertSent(ContentRevisionNeeded::class, fn ($mail) => $mail->hasTo($designer->email));
        Mail::assertNotSent(RequestStatusChanged::class);
    }

    public function test_the_clinician_is_told_where_their_feedback_went(): void
    {
        $request = $this->contentAwaitingApproval();
        $this->designerOn($request);

        $this->post(route('approval.respond', $request->approvers->first()->token), [
            'status' => 'rejected',
            'notes' => 'Needs a safety-netting line.',
        ])->assertSuccessful()
            ->assertSee('Your notes have gone to the person writing this copy');
    }

    public function test_a_change_request_is_still_declined_when_an_approver_rejects_it(): void
    {
        $request = $this->contentAwaitingApproval();
        $request->updateQuietly(['request_type' => 'change', 'status' => 'referred', 'page_url' => '/a-page']);

        $this->post(route('approval.respond', $request->approvers->first()->token), [
            'status' => 'rejected',
            'notes' => 'No.',
        ]);

        // The change lane is unchanged: a rejection there ends the request.
        $this->assertSame('declined', $request->fresh()->status);
        $this->assertNotNull($request->fresh()->rejection_reason);
        Mail::assertNotSent(ContentRevisionNeeded::class);
    }

    public function test_the_status_log_records_the_status_it_actually_came_from(): void
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
            'new_status' => 'in_progress',
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
