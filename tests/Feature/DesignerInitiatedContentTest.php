<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ClinicalApprover;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Content the team starts itself. It has no requester and may have no site, which
 * every admin view was written assuming it could not happen — a missed guard is a
 * 500 on a real page, so the bare case is exercised everywhere it can appear.
 */
class DesignerInitiatedContentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** Nothing filled in beyond the one required field. */
    private function bareContent(): ChangeRequest
    {
        return ChangeRequest::create([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => 'content',
            'site_id' => null,
            'page_url' => 'new-content',
            'page_title' => 'Podiatry first appointment explainer',
            'cpt_slug' => 'content',
            'is_new_page' => true,
            'status' => 'suggested',
            'requester_name' => null,
            'requester_email' => null,
        ]);
    }

    public function test_the_form_is_reachable_from_the_request_list(): void
    {
        Site::create(['name' => 'HCRG', 'domain' => 'hcrg.test', 'is_active' => true]);

        $this->loginAsAdmin();

        $this->get(route('admin.requests.index'))
            ->assertSuccessful()
            ->assertSee(route('admin.requests.content.create'), false);

        $this->get(route('admin.requests.content.create'))
            ->assertSuccessful()
            ->assertSee('Working title')
            // The brief and the type list are the same questions the public form asks.
            ->assertSee('Explains a service and how it works')
            ->assertSee('Patients &amp; service users', false);
    }

    public function test_a_designer_can_create_content_nobody_asked_for(): void
    {
        $this->loginAsAdmin();

        $this->post(route('admin.requests.content.store'), [
            'page_title' => 'Podiatry first appointment explainer',
        ])->assertRedirect();

        $request = ChangeRequest::firstOrFail();

        $this->assertSame('content', $request->request_type);
        // The same gate as a suggestion from the public form: nothing is committed
        // until it is agreed and funded, whoever raised it.
        $this->assertSame('suggested', $request->status);
        $this->assertNull($request->requester_email);
        $this->assertNull($request->site_id);
    }

    public function test_the_designer_can_fill_in_everything_at_once(): void
    {
        $site = Site::create(['name' => 'HCRG', 'domain' => 'hcrg.test', 'is_active' => true]);
        $other = Site::create(['name' => 'Second site', 'domain' => 'second.test', 'is_active' => true]);

        $this->loginAsAdmin();

        $this->post(route('admin.requests.content.store'), [
            'page_title' => 'Podiatry first appointment explainer',
            'content_type' => 'appointment_prep',
            'site_id' => $site->id,
            // The main home repeated here is noise, not a second site.
            'additional_site_ids' => [$site->id, $other->id],
            'priority' => 'high',
            'draft_content' => 'The copy, already written.',
            'brief' => [
                'achieve' => 'Stop people ringing to ask what happens first.',
                'audience' => ['patients', 'families'],
                'know_or_do' => 'Know what to bring.',
                'already_exists' => 'no',
            ],
        ])->assertRedirect();

        $request = ChangeRequest::firstOrFail();

        $this->assertSame('appointment_prep', $request->content_type);
        $this->assertSame('The copy, already written.', $request->draft_content);
        $this->assertSame('Stop people ringing to ask what happens first.', $request->content_brief['achieve']);
        $this->assertSame([$other->id], $request->additionalSites->pluck('id')->all());
    }

    public function test_a_working_title_is_the_one_thing_required(): void
    {
        $this->loginAsAdmin();

        $this->post(route('admin.requests.content.store'), [])
            ->assertSessionHasErrors('page_title');

        $this->assertSame(0, ChangeRequest::count());
    }

    public function test_blank_brief_answers_are_not_stored_as_empty_strings(): void
    {
        $this->loginAsAdmin();

        $this->post(route('admin.requests.content.store'), [
            'page_title' => 'A title',
            'brief' => ['achieve' => '', 'know_or_do' => '', 'measure' => ''],
        ]);

        // An all-empty brief is no brief; storing it makes "has a brief" untrue.
        $this->assertNull(ChangeRequest::firstOrFail()->content_brief);
    }

    public function test_the_admin_page_renders_with_no_requester_and_no_site(): void
    {
        $request = $this->bareContent();

        $this->loginAsAdmin();

        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertSee('No requester')
            ->assertSee('Podiatry first appointment explainer');
    }

    public function test_the_lists_render_with_no_requester_and_no_site(): void
    {
        $this->bareContent();

        $this->loginAsAdmin();

        $this->get(route('admin.requests.index'))->assertSuccessful();
        $this->get(route('admin.dashboard'))->assertSuccessful();
    }

    public function test_the_tracking_page_renders_with_no_requester_and_no_site(): void
    {
        $request = $this->bareContent();

        $this->get(\App\Http\Controllers\PublicSite\TrackingController::signedUrl($request))
            ->assertSuccessful();
    }

    public function test_the_approval_page_renders_with_no_requester_and_no_site(): void
    {
        $request = $this->bareContent();
        $request->updateQuietly(['status' => 'awaiting_approval', 'draft_content' => 'The copy.']);

        $clinician = ClinicalApprover::create([
            'name' => 'Dr Helen Johal', 'email' => 'h.johal@example.nhs.uk', 'is_active' => true,
        ]);

        $this->loginAsAdmin();
        $this->post(route('admin.requests.approvers.add', $request), ['clinical_approver_id' => $clinician->id])
            ->assertRedirect();

        $token = $request->fresh()->approvers->first()->token;

        $this->get(route('approval.show', $token))
            ->assertSuccessful()
            ->assertSee('The copy.');
    }

    public function test_it_can_go_all_the_way_to_done_without_trying_to_email_a_requester(): void
    {
        $request = $this->bareContent();

        $this->loginAsAdmin();

        foreach (['in_progress', 'awaiting_approval', 'approved', 'done'] as $status) {
            $this->patch(route('admin.requests.status', $request), ['status' => $status])
                ->assertRedirect();
        }

        $this->assertSame('done', $request->fresh()->status);
        // No requester means nobody to write to; a send to an empty address would
        // either throw or log a delivery that never happened.
        Mail::assertNothingSent();
        $this->assertSame(0, \App\Models\EmailLog::whereNull('recipient_email')->count());
    }

    public function test_it_stays_off_the_top_requesters_list(): void
    {
        $this->bareContent();

        $this->loginAsAdmin();

        $this->get(route('admin.dashboard'))
            ->assertSuccessful()
            // An empty row under "Top requesters" is not a person.
            ->assertDontSee('mailto:"');
    }
}
