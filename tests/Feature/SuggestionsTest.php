<?php

namespace Tests\Feature;

use App\Mail\WatchConfirmation;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestWatcher;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SuggestionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function published(array $overrides = []): ChangeRequest
    {
        $site = Site::create(['name' => 'HCRG Care Group', 'domain' => 'hcrgcaregroup.com', 'is_active' => true]);

        return ChangeRequest::create(array_merge([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => 'content',
            'site_id' => $site->id,
            'page_url' => 'new-content',
            'cpt_slug' => 'content',
            'status' => 'awaiting_funding',
            'requester_name' => 'Priya Sharma',
            'requester_email' => 'priya.sharma@example.com',
            'content_type' => 'service_explainer',
            'public_title' => 'What happens at your first community nursing appointment',
            'content_brief' => [
                'achieve' => 'SECRET_BRIEF_TEXT',
                'audience' => ['patients'],
                'know_or_do' => 'SECRET_KNOW_TEXT',
                'already_exists' => 'no',
            ],
            'draft_content' => 'SECRET_DRAFT_COPY',
            'rejection_reason' => 'SECRET_DECLINE_REASON',
            'published_url' => 'https://hcrgcaregroup.com/secret-address',
        ], $overrides));
    }

    /**
     * The one that matters: there is no sign-in, so anything on this page is published.
     */
    public function test_the_queue_leaks_none_of_the_private_fields(): void
    {
        $request = $this->published();
        $request->approvers()->create([
            'name' => 'Dr Helen Johal', 'email' => 'h.johal@nhs.net', 'status' => 'approved',
        ]);

        $response = $this->get(route('suggestions'))->assertSuccessful();

        foreach ([
            'Priya Sharma',                 // who suggested it
            'priya.sharma@example.com',
            'SECRET_BRIEF_TEXT',            // the brief
            'SECRET_KNOW_TEXT',
            'SECRET_DRAFT_COPY',            // draft copy
            'SECRET_DECLINE_REASON',        // why something was declined
            'Dr Helen Johal',               // the clinical approver
            'secret-address',               // the URL itself
        ] as $private) {
            $response->assertDontSee($private, false);
        }
    }

    public function test_the_queue_shows_the_public_fields_including_site_titles(): void
    {
        $request = $this->published();
        $other = Site::create(['name' => 'Virgin Care Services', 'domain' => 'virgincare.co.uk', 'is_active' => true]);
        $request->additionalSites()->attach($other->id);

        $this->get(route('suggestions'))
            ->assertSuccessful()
            ->assertSee('What happens at your first community nursing appointment')
            ->assertSee($request->reference)
            ->assertSee('Awaiting Funding')
            // Site titles, not addresses.
            ->assertSee('HCRG Care Group')
            ->assertSee('Virgin Care Services');
    }

    public function test_a_suggestion_without_a_public_title_is_not_listed(): void
    {
        // A requester's own words were not written for publication.
        $this->published(['public_title' => null, 'reference' => 'CR-HIDDEN']);

        $this->get(route('suggestions'))->assertSuccessful()->assertDontSee('CR-HIDDEN');
    }

    public function test_change_requests_never_appear_on_the_content_queue(): void
    {
        $this->published(['request_type' => 'change', 'reference' => 'CR-ACHANGE']);

        $this->get(route('suggestions'))->assertSuccessful()->assertDontSee('CR-ACHANGE');
    }

    public function test_the_duplicate_search_returns_matches_for_the_brief(): void
    {
        $this->published();

        $this->getJson(route('suggestions.search', ['q' => 'community nursing']))
            ->assertSuccessful()
            ->assertJsonPath('results.0.title', 'What happens at your first community nursing appointment');
    }

    public function test_the_duplicate_search_ignores_very_short_queries(): void
    {
        $this->published();

        $this->getJson(route('suggestions.search', ['q' => 'wh']))
            ->assertSuccessful()
            ->assertJsonCount(0, 'results');
    }

    public function test_watching_sends_a_confirmation_and_stays_silent_until_confirmed(): void
    {
        $request = $this->published();

        $this->post(route('suggestions.watch', $request->reference), ['email' => 'watcher@example.com'])
            ->assertRedirect();

        Mail::assertSent(WatchConfirmation::class);

        $watcher = ChangeRequestWatcher::where('email', 'watcher@example.com')->firstOrFail();
        // Public sign-up means an address is all it takes, so nothing is sent yet.
        $this->assertNull($watcher->confirmed_at);
        $this->assertCount(0, ChangeRequestWatcher::confirmed()->get());
    }

    public function test_confirming_and_unsubscribing_work_by_token(): void
    {
        $request = $this->published();
        $this->post(route('suggestions.watch', $request->reference), ['email' => 'watcher@example.com']);
        $watcher = ChangeRequestWatcher::where('email', 'watcher@example.com')->firstOrFail();

        $this->get(route('suggestions.confirm', $watcher->token))->assertRedirect();
        $this->assertNotNull($watcher->fresh()->confirmed_at);

        $this->get(route('suggestions.unsubscribe', $watcher->token))->assertRedirect();
        $this->assertNull(ChangeRequestWatcher::find($watcher->id));
    }

    public function test_declined_and_cancelled_suggestions_drop_off_the_list(): void
    {
        $this->published(['status' => 'declined', 'reference' => 'CR-DECLINED']);
        $this->published(['status' => 'cancelled', 'reference' => 'CR-CANCELLED']);
        $this->published(['status' => 'awaiting_funding', 'reference' => 'CR-LIVE']);

        $this->get(route('suggestions'))
            ->assertSuccessful()
            ->assertSee('CR-LIVE')
            ->assertDontSee('CR-DECLINED')
            ->assertDontSee('CR-CANCELLED');
    }

    public function test_a_declined_suggestion_is_not_offered_as_a_duplicate_match(): void
    {
        $this->published(['status' => 'declined']);

        // Suggesting something we already turned down should not read as "it exists".
        $this->getJson(route('suggestions.search', ['q' => 'community nursing']))
            ->assertSuccessful()
            ->assertJsonCount(0, 'results');
    }
}
