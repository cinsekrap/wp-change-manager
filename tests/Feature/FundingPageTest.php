<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The page a content designer takes to a funding conversation. Funding is decided
 * elsewhere; this shows what is waiting and what it costs, and records the answer
 * through the same bulk action the request list uses.
 */
class FundingPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function content(string $status, ?string $hours, array $overrides = []): ChangeRequest
    {
        $site = Site::firstOrCreate(['domain' => 'hcrg.test'], ['name' => 'HCRG', 'is_active' => true]);

        return ChangeRequest::create(array_merge([
            'reference' => 'WCR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => 'content',
            'site_id' => $site->id,
            'page_url' => 'new-content',
            'page_title' => 'A working title',
            'cpt_slug' => 'content',
            'status' => $status,
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'content_type' => 'service_explainer',
            'estimated_hours' => $hours,
            'content_brief' => ['achieve' => 'Stop people ringing to ask what happens first.'],
        ], $overrides));
    }

    public function test_it_lists_everything_waiting_on_a_decision(): void
    {
        $suggested = $this->content('suggested', null);
        $scoped = $this->content('scoped', '8');
        $awaiting = $this->content('awaiting_funding', '12.5');

        $this->loginAsAdmin();

        $this->get(route('admin.funding'))
            ->assertSuccessful()
            ->assertSee($suggested->reference)
            ->assertSee($scoped->reference)
            ->assertSee($awaiting->reference);
    }

    public function test_it_leaves_out_anything_already_decided(): void
    {
        $decided = [
            $this->content('in_progress', '8'),
            $this->content('done', '8'),
            $this->content('declined', '8'),
        ];
        $waiting = $this->content('awaiting_funding', '4');

        $this->loginAsAdmin();

        $response = $this->get(route('admin.funding'))->assertSuccessful();

        $response->assertSee($waiting->reference);
        foreach ($decided as $request) {
            // Naming each one, rather than counting references — a row carries
            // its reference more than once, and a count says nothing about which.
            $response->assertDontSee($request->reference);
        }
    }

    public function test_change_requests_never_appear(): void
    {
        $change = $this->content('suggested', '8');
        $change->updateQuietly(['request_type' => 'change', 'status' => 'requested']);

        $this->loginAsAdmin();

        // Only content goes through a funding decision.
        $this->get(route('admin.funding'))->assertSuccessful()->assertDontSee($change->reference);
    }

    public function test_the_hours_are_totalled(): void
    {
        $this->content('scoped', '8');
        $this->content('awaiting_funding', '12.5');
        $this->content('suggested', null);

        $this->loginAsAdmin();

        $this->get(route('admin.funding'))
            ->assertSuccessful()
            ->assertSee('20.5')
            // The unsized one is called out rather than silently counted as zero.
            ->assertSee('Still to size up');
    }

    public function test_an_unsized_piece_is_shown_as_unsized_not_as_zero(): void
    {
        $this->content('suggested', null);

        $this->loginAsAdmin();

        $this->get(route('admin.funding'))->assertSuccessful()->assertSee('not sized');
    }

    public function test_the_longest_wait_comes_first(): void
    {
        $newer = $this->content('scoped', '4');
        $older = $this->content('scoped', '4');
        $older->forceFill(['created_at' => now()->subMonths(3)])->saveQuietly();

        $this->loginAsAdmin();

        $html = $this->get(route('admin.funding'))->assertSuccessful()->getContent();

        // The wait is part of the argument, so it leads.
        $this->assertLessThan(strpos($html, $newer->reference), strpos($html, $older->reference));
    }

    public function test_recording_the_decision_moves_them_on(): void
    {
        $one = $this->content('awaiting_funding', '8');
        $two = $this->content('scoped', '4');
        $untouched = $this->content('suggested', null);

        $this->loginAsAdmin();

        // The page uses the request list's existing bulk action rather than a
        // second approval mechanism of its own.
        $this->postJson(route('admin.requests.bulk.status'), [
            'ids' => [$one->id, $two->id],
            'status' => 'in_progress',
        ])->assertSuccessful();

        $this->assertSame('in_progress', $one->fresh()->status);
        $this->assertSame('in_progress', $two->fresh()->status);
        $this->assertSame('suggested', $untouched->fresh()->status);

        // And they drop off the list, because they are no longer waiting.
        $this->get(route('admin.funding'))
            ->assertSuccessful()
            ->assertDontSee($one->reference)
            ->assertSee($untouched->reference);
    }

    public function test_it_is_reachable_from_every_admin_page(): void
    {
        $this->loginAsAdmin();

        foreach ([route('admin.dashboard'), route('admin.requests.index')] as $url) {
            $this->get($url)->assertSuccessful()->assertSee(route('admin.funding'), false);
        }
    }

    public function test_it_needs_an_admin(): void
    {
        $this->get(route('admin.funding'))->assertRedirect();
    }
}
