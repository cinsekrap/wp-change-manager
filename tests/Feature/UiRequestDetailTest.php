<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestItem;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The request page carried eleven cards for a content request, added a feature at
 * a time, with everything open at once. They all apply — what you are working on,
 * what you are working from, and what has happened are three different questions,
 * asked at different moments.
 */
class UiRequestDetailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function request(string $type, array $overrides = []): ChangeRequest
    {
        $site = Site::firstOrCreate(['domain' => 'hcrg.test'], ['name' => 'HCRG', 'is_active' => true]);

        $request = ChangeRequest::create(array_merge([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => $type,
            'site_id' => $site->id,
            'page_url' => $type === 'content' ? 'new-content' : '/a-page',
            'page_title' => 'A page',
            'cpt_slug' => $type === 'content' ? 'content' : 'pages',
            'status' => $type === 'content' ? 'in_progress' : 'requested',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'content_type' => 'service_explainer',
            'draft_content' => $type === 'content' ? 'The copy.' : null,
            'content_brief' => $type === 'content' ? ['achieve' => 'A distinctive purpose.'] : null,
            'access_recipient_name' => $type === 'access' ? 'Jane Doe' : null,
            'access_recipient_email' => $type === 'access' ? 'jane@example.com' : null,
        ], $overrides));

        if ($type === 'change') {
            ChangeRequestItem::create([
                'change_request_id' => $request->id,
                'action_type' => 'add',
                'description' => 'Add a phone number.',
                'sort_order' => 0,
            ]);
        }

        return $request;
    }

    private function panels(string $html): array
    {
        preg_match_all('/data-tab-panel="([a-z]+)"([^>]*)>/', $html, $m, PREG_SET_ORDER);

        return collect($m)->mapWithKeys(
            fn ($p) => [$p[1] => ! str_contains($p[2], 'hidden')]
        )->all();
    }

    public function test_content_gets_three_tabs_and_opens_on_the_work(): void
    {
        $this->loginAsAdmin();
        $html = $this->get(route('admin.requests.show', $this->request('content')))
            ->assertSuccessful()->getContent();

        $this->assertSame(['work' => true, 'brief' => false, 'history' => false], $this->panels($html));
    }

    public function test_a_request_with_no_brief_gets_no_brief_tab(): void
    {
        $this->loginAsAdmin();

        foreach (['change', 'access'] as $type) {
            $html = $this->get(route('admin.requests.show', $this->request($type)))
                ->assertSuccessful()->getContent();

            // An empty third tab is worse than two.
            $this->assertSame(['work' => true, 'history' => false], $this->panels($html),
                "A {$type} request has the wrong tabs");
        }
    }

    public function test_the_work_you_do_is_on_the_first_tab(): void
    {
        $request = $this->request('content');
        $this->loginAsAdmin();

        $html = $this->get(route('admin.requests.show', $request))->assertSuccessful()->getContent();
        $work = $this->panelBody($html, 'work');

        $this->assertStringContainsString('Draft copy', $work);
        $this->assertStringContainsString('The copy.', $work);
        $this->assertStringContainsString('Where it went live', $work);
    }

    public function test_what_you_work_from_is_on_the_brief_tab(): void
    {
        $request = $this->request('content');
        $this->loginAsAdmin();

        $brief = $this->panelBody(
            $this->get(route('admin.requests.show', $request))->getContent(), 'brief'
        );

        $this->assertStringContainsString('A distinctive purpose.', $brief);
    }

    public function test_what_has_happened_is_on_the_history_tab(): void
    {
        $request = $this->request('content');
        $this->loginAsAdmin();

        $history = $this->panelBody(
            $this->get(route('admin.requests.show', $request))->getContent(), 'history'
        );

        $this->assertStringContainsString('Activity', $history);
    }

    public function test_the_sidebar_holds_only_what_you_change(): void
    {
        $request = $this->request('change');
        $this->loginAsAdmin();

        $html = $this->get(route('admin.requests.show', $request))->assertSuccessful()->getContent();
        $sidebar = substr($html, strrpos($html, '<div class="space-y-4">'));

        // Reference material read occasionally does not belong beside the
        // controls you use every time.
        $this->assertStringNotContainsString('Page history', $sidebar);
        $this->assertStringNotContainsString('Activity', $sidebar);
        $this->assertStringContainsString('Status', $sidebar);
        $this->assertStringContainsString('Approvals', $sidebar);
    }

    public function test_the_header_answers_what_and_where_before_any_scrolling(): void
    {
        $request = $this->request('content', ['page_title' => 'First appointment explainer']);
        $request->assignee()->associate(\App\Models\User::factory()->create(['name' => 'Sam Okafor']))->save();

        $this->loginAsAdmin();

        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertSee('First appointment explainer')
            ->assertSee($request->reference)
            ->assertSee('Owner: Sam Okafor')
            ->assertSee('HCRG');
    }

    public function test_content_the_team_started_says_so_in_the_header(): void
    {
        $request = $this->request('content', ['requester_name' => null, 'requester_email' => null]);
        $this->loginAsAdmin();

        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertSee('by the content team');
    }

    private function panelBody(string $html, string $panel): string
    {
        $start = strpos($html, 'data-tab-panel="'.$panel.'"');
        $this->assertNotFalse($start, "No '{$panel}' panel on the page");

        $next = collect(['work', 'brief', 'history'])
            ->map(fn ($p) => strpos($html, 'data-tab-panel="'.$p.'"'))
            ->filter(fn ($pos) => $pos !== false && $pos > $start)
            ->min();

        return substr($html, $start, ($next ?: strlen($html)) - $start);
    }

    public function test_what_the_requester_told_us_sits_with_the_work(): void
    {
        $request = $this->request('change', [
            'check_answers' => [['question_text' => 'Checked for accuracy?', 'answer' => 'Yes', 'pass' => true]],
        ]);
        $this->loginAsAdmin();

        $html = $this->get(route('admin.requests.show', $request))->assertSuccessful()->getContent();

        // Answers given before submitting are context for doing the work, not a
        // record of what has happened since.
        $this->assertStringContainsString('Checked for accuracy?', $this->panelBody($html, 'work'));
    }

    public function test_page_history_moved_out_of_the_sidebar_into_history(): void
    {
        $site = Site::firstOrCreate(['domain' => 'hcrg.test'], ['name' => 'HCRG', 'is_active' => true]);

        // Two change requests against the same page, so history has something in it.
        $earlier = $this->request('change', ['page_url' => '/shared-page']);
        $current = $this->request('change', ['page_url' => '/shared-page']);

        $this->loginAsAdmin();
        $html = $this->get(route('admin.requests.show', $current))->assertSuccessful()->getContent();

        $this->assertStringContainsString($earlier->reference, $this->panelBody($html, 'history'));
    }

    public function test_the_unlock_link_still_points_into_the_draft(): void
    {
        $request = $this->request('content');
        $request->approvers()->create([
            'name' => 'Dr Helen Johal',
            'email' => 'h.johal@example.nhs.uk',
            'status' => 'approved',
            'responded_at' => now(),
            'approved_content_hash' => $request->draftContentHash(),
            'approved_content_snapshot' => $request->draft_content,
        ]);

        $this->loginAsAdmin();

        // The link carries #draft, and the tab script resolves an element id to
        // the panel holding it.
        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertSee('unlock_draft=1#draft', false);
    }

    public function test_adding_a_note_sits_with_the_work(): void
    {
        $request = $this->request('content');
        $this->loginAsAdmin();

        $html = $this->get(route('admin.requests.show', $request))->assertSuccessful()->getContent();

        // Writing a note is something you do while working on a request.
        $this->assertStringContainsString(
            route('admin.requests.notes', $request),
            $this->panelBody($html, 'work')
        );
    }

    public function test_reading_what_happened_stays_under_history(): void
    {
        $request = $this->request('content');
        $request->notes()->create(['user_id' => null, 'note' => 'A note somebody left earlier.']);

        $this->loginAsAdmin();
        $html = $this->get(route('admin.requests.show', $request))->assertSuccessful()->getContent();

        $history = $this->panelBody($html, 'history');
        $this->assertStringContainsString('A note somebody left earlier.', $history);

        // The form is not duplicated into the timeline it feeds.
        $this->assertStringNotContainsString(route('admin.requests.notes', $request), $history);
    }

    public function test_a_note_can_still_be_added(): void
    {
        $request = $this->request('content');
        $this->loginAsAdmin();

        $this->post(route('admin.requests.notes', $request), ['note' => 'Chased the approver.'])
            ->assertRedirect();

        $this->assertSame('Chased the approver.', $request->notes()->latest()->first()->note);
    }

    public function test_one_box_covers_both_a_note_and_a_question(): void
    {
        $request = $this->request('change');
        $this->loginAsAdmin();

        $work = $this->panelBody(
            $this->get(route('admin.requests.show', $request))->assertSuccessful()->getContent(), 'work'
        );

        // Two textareas doing similar-looking things was the problem.
        $this->assertStringContainsString('A note on the job', $work);
        $this->assertStringContainsString('A question for Jane Doe', $work);
        $this->assertStringContainsString('SLA clock stops', $work);
    }

    public function test_the_sidebar_no_longer_asks_for_clarification(): void
    {
        $request = $this->request('change');
        $this->loginAsAdmin();

        $html = $this->get(route('admin.requests.show', $request))->assertSuccessful()->getContent();
        $sidebar = substr($html, strrpos($html, '<div class="space-y-4">'));

        $this->assertStringNotContainsString('clarification_message', $sidebar);
    }

    public function test_a_question_emails_the_requester_and_stops_the_clock(): void
    {
        $request = $this->request('change');
        $this->loginAsAdmin();

        $this->post(route('admin.requests.notes', $request), [
            'note' => 'Which of the two phone numbers is correct?',
            'kind' => 'question',
        ])->assertRedirect();

        $request->refresh();
        $this->assertSame('awaiting_user', $request->status);
        $this->assertSame('Which of the two phone numbers is correct?', $request->clarification_message);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ClarificationRequested::class);
    }

    public function test_a_note_changes_nothing_and_sends_nothing(): void
    {
        $request = $this->request('change');
        $this->loginAsAdmin();

        $this->post(route('admin.requests.notes', $request), [
            'note' => 'Chased the approver.',
            'kind' => 'note',
        ])->assertRedirect();

        $this->assertSame('requested', $request->fresh()->status);
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_nobody_can_be_asked_when_there_is_no_requester(): void
    {
        $request = $this->request('content', ['requester_name' => null, 'requester_email' => null]);
        $this->loginAsAdmin();

        // Content the team started itself has nobody to ask, and the request
        // would sit at awaiting_user for an answer that never comes.
        $work = $this->panelBody(
            $this->get(route('admin.requests.show', $request))->getContent(), 'work'
        );
        $this->assertStringContainsString('no requester', $work);
        $this->assertStringNotContainsString('A question for', $work);

        $this->post(route('admin.requests.notes', $request), [
            'note' => 'Anyone there?', 'kind' => 'question',
        ])->assertSessionHas('error');

        $this->assertSame('in_progress', $request->fresh()->status);
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_a_closed_request_cannot_be_asked_about(): void
    {
        $request = $this->request('change', ['status' => 'done']);
        $this->loginAsAdmin();

        // The status badge already says it is closed; the form does not repeat it.
        $work = $this->panelBody(
            $this->get(route('admin.requests.show', $request))->getContent(), 'work'
        );
        $this->assertStringNotContainsString('closed', $work);
        $this->assertStringNotContainsString('A question for', $work);

        $this->post(route('admin.requests.notes', $request), [
            'note' => 'Still fine?', 'kind' => 'question',
        ])->assertSessionHas('error');

        $this->assertSame('done', $request->fresh()->status);
    }
}
