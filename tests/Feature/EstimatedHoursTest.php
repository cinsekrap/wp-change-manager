<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestWatcher;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The hours a content designer works out at Sized Up, which the funding decision
 * turns on. It lives here because the tool already promised requesters we would
 * estimate it — but it is ours, not theirs, so the point of most of this file is
 * proving it never reaches them.
 */
class EstimatedHoursTest extends TestCase
{
    /** Distinctive enough that finding it anywhere is unambiguous. */
    private const HOURS = '137.5';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function content(array $overrides = []): ChangeRequest
    {
        $site = Site::firstOrCreate(['domain' => 'hcrg.test'], ['name' => 'HCRG', 'is_active' => true]);

        return ChangeRequest::create(array_merge([
            'reference' => 'WCR-20260905-042',
            'request_type' => 'content',
            'site_id' => $site->id,
            'page_url' => 'new-content',
            'page_title' => 'A working title',
            'cpt_slug' => 'content',
            'status' => 'awaiting_funding',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'content_type' => 'service_explainer',
            'public_title' => 'A published suggestion',
            'draft_content' => 'Some copy.',
            'estimated_hours' => self::HOURS,
            // The access-lane templates build links from this; without it they
            // cannot render at all and the leak check never runs.
            'training_token' => 'tok-'.bin2hex(random_bytes(8)),
            'access_recipient_name' => 'Jane Doe',
            'access_recipient_email' => 'jane@example.com',
        ], $overrides));
    }

    public function test_a_designer_can_record_and_clear_the_estimate(): void
    {
        $request = $this->content(['estimated_hours' => null]);
        $this->loginAsAdmin();

        $this->patch(route('admin.requests.estimate', $request), ['estimated_hours' => '7.5'])
            ->assertRedirect();
        $this->assertEquals(7.5, (float) $request->fresh()->estimated_hours);

        $this->patch(route('admin.requests.estimate', $request), ['estimated_hours' => ''])
            ->assertRedirect();
        $this->assertNull($request->fresh()->estimated_hours);
    }

    public function test_a_nonsense_estimate_is_refused(): void
    {
        $request = $this->content();
        $this->loginAsAdmin();

        foreach (['-1', 'eight', '99999'] as $bad) {
            $this->patch(route('admin.requests.estimate', $request), ['estimated_hours' => $bad])
                ->assertSessionHasErrors('estimated_hours');
        }

        $this->assertEquals((float) self::HOURS, (float) $request->fresh()->estimated_hours);
    }

    public function test_the_designer_sees_it_on_the_request(): void
    {
        $request = $this->content();
        $this->loginAsAdmin();

        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertSee('Estimated hours')
            ->assertSee('Internal only');
    }

    public function test_the_create_form_records_it(): void
    {
        $this->loginAsAdmin();

        $this->post(route('admin.requests.content.store'), [
            'page_title' => 'Something the team wants',
            'estimated_hours' => '12',
        ])->assertRedirect();

        $this->assertEquals(12.0, (float) ChangeRequest::firstOrFail()->estimated_hours);
    }

    // --- Everything below is about it not getting out ----------------------

    public function test_it_is_not_on_the_public_suggestions_list(): void
    {
        $this->content();

        $this->get(route('suggestions'))
            ->assertSuccessful()
            ->assertSee('A published suggestion')
            ->assertDontSee(self::HOURS);
    }

    public function test_it_is_not_in_the_public_duplicate_search(): void
    {
        $this->content();

        $this->get(route('suggestions.search', ['q' => 'published suggestion']))
            ->assertSuccessful()
            ->assertDontSee(self::HOURS);
    }

    public function test_it_is_not_on_the_requesters_tracking_page(): void
    {
        $request = $this->content();

        $this->get(\App\Http\Controllers\PublicSite\TrackingController::signedUrl($request))
            ->assertSuccessful()
            ->assertDontSee(self::HOURS);
    }

    public function test_it_is_not_on_the_confirmation_page(): void
    {
        $request = $this->content();

        $this->get(\Illuminate\Support\Facades\URL::temporarySignedRoute(
            'confirmation', now()->addDay(), ['reference' => $request->reference]
        ))->assertSuccessful()->assertDontSee(self::HOURS);
    }

    public function test_it_is_in_no_email_a_requester_or_approver_receives(): void
    {
        $request = $this->content();
        $approver = ChangeRequestApprover::create([
            'change_request_id' => $request->id,
            'name' => 'Dr Helen Johal',
            'email' => 'h.johal@example.nhs.uk',
            'status' => 'pending',
            'token' => ChangeRequestApprover::generateToken(),
        ]);
        $watcher = ChangeRequestWatcher::create([
            'change_request_id' => $request->id,
            'email' => 'watcher@example.com',
            'token' => ChangeRequestWatcher::generateToken(),
        ]);
        $user = User::factory()->create();

        // Every mailable that carries a change request, built the way the app
        // builds it. A new template that leaks the number fails here.
        $mailables = [
            new \App\Mail\AccessGranted($request),
            new \App\Mail\ApprovalDeclined($request, $approver),
            new \App\Mail\ApprovalOverridden($request, $approver),
            new \App\Mail\ApprovalRequested($request, $approver),
            new \App\Mail\ClarificationRequested($request),
            new \App\Mail\ClarificationResponded($request, 'A comment', 1),
            new \App\Mail\ContentAwaitingFunding($request, $watcher),
            new \App\Mail\ContentAwaitingFunding($request, null),
            new \App\Mail\ContentPublished($request, $watcher),
            new \App\Mail\ContentPublished($request, null),
            new \App\Mail\ContentRevisionNeeded($request, $approver),
            new \App\Mail\ContentSuggestionReceived($request),
            new \App\Mail\GroupApprovalSatisfied($request, $approver, 'Someone'),
            new \App\Mail\NewRequestAlert($request),
            new \App\Mail\RequestAssigned($request, $user),
            new \App\Mail\RequestChase($request),
            new \App\Mail\RequestOnHold($request),
            new \App\Mail\RequestStatusChanged($request, 'scoped', 'awaiting_funding'),
            new \App\Mail\RequestSubmitted($request),
            new \App\Mail\ScheduledForActionToday($request),
            new \App\Mail\TrainingConfirmed($request),
            new \App\Mail\TrainingRequested($request),
            new \App\Mail\WatchConfirmation($request, $watcher),
        ];

        $leaking = [];
        foreach ($mailables as $mailable) {
            if (str_contains($mailable->render(), self::HOURS)) {
                $leaking[] = class_basename($mailable);
            }
        }

        $this->assertSame([], $leaking, 'These emails carry the internal estimate: '.implode(', ', $leaking));
    }

    public function test_the_funding_approver_is_the_one_person_who_does_see_it(): void
    {
        $request = $this->content();
        $approver = \App\Models\FundingApprover::create([
            'name' => 'Sam Okafor', 'email' => 'sam@example.com', 'is_active' => true,
        ]);

        $round = \App\Models\FundingRound::create([
            'reference' => 'FR-20260905-001',
            'funding_approver_id' => $approver->id,
            'approver_name' => $approver->label(),
            'approver_email' => $approver->email,
            'status' => 'pending',
            'token' => \App\Models\FundingRound::generateToken(),
            'total_hours' => self::HOURS,
        ]);
        $round->items()->create([
            'change_request_id' => $request->id,
            'estimated_hours' => self::HOURS,
        ]);

        // The exception that proves the rule: hiding the number from the person
        // being asked to pay for it would make the ask meaningless.
        $this->assertStringContainsString(self::HOURS, (new \App\Mail\FundingRequested($round->fresh()))->render());
    }

    public function test_every_mailable_carrying_a_request_is_covered_above(): void
    {
        $onDisk = collect(glob(app_path('Mail/*.php')))
            ->map(fn ($f) => basename($f, '.php'))
            // Takes no change request, so it cannot carry the estimate.
            ->reject(fn ($n) => $n === 'PasswordResetMail')
            // Goes to the funding approver, and carries the hours on purpose —
            // covered by its own test above.
            ->reject(fn ($n) => $n === 'FundingRequested')
            ->values()->all();

        $covered = [
            'AccessGranted', 'ApprovalDeclined', 'ApprovalOverridden', 'ApprovalRequested',
            'ClarificationRequested', 'ClarificationResponded', 'ContentAwaitingFunding',
            'ContentPublished', 'ContentRevisionNeeded', 'ContentSuggestionReceived', 'GroupApprovalSatisfied',
            'NewRequestAlert', 'RequestAssigned', 'RequestChase', 'RequestOnHold',
            'RequestStatusChanged', 'RequestSubmitted', 'ScheduledForActionToday',
            'TrainingConfirmed', 'TrainingRequested', 'WatchConfirmation',
        ];

        // A new mailable added without being listed above would otherwise be
        // untested for this, which is exactly how a leak gets in.
        $this->assertSame([], array_values(array_diff($onDisk, $covered)),
            'New mailables not covered by the leak test: '.implode(', ', array_diff($onDisk, $covered)));
    }

    public function test_the_admin_export_does_carry_it(): void
    {
        $this->content();
        $this->loginAsAdmin();

        $csv = $this->get(route('admin.requests.export'))->assertSuccessful()->streamedContent();

        // Admin-only, and the whole point is being able to total unfunded hours.
        $this->assertStringContainsString('Estimated Hours', $csv);
        $this->assertStringContainsString(self::HOURS, $csv);
    }
}
