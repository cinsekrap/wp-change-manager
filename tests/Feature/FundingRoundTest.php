<?php

namespace Tests\Feature;

use App\Mail\FundingRequested;
use App\Models\ChangeRequest;
use App\Models\FundingApprover;
use App\Models\FundingRound;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * One ask for money covering several pieces. Deliberately not the per-request
 * approver machinery: that binds a clinician to a version of the copy, and would
 * send a budget holder one email and one decision per item.
 */
class FundingRoundTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function content(string $status = 'scoped', ?string $hours = '8'): ChangeRequest
    {
        $site = Site::firstOrCreate(['domain' => 'hcrg.test'], ['name' => 'HCRG', 'is_active' => true]);

        return ChangeRequest::create([
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
            'content_brief' => ['achieve' => 'Stop the phone calls.'],
        ]);
    }

    private function approver(array $overrides = []): FundingApprover
    {
        return FundingApprover::create(array_merge([
            'name' => 'Sam Okafor',
            'email' => 'sam.okafor@example.com',
            'job_title' => 'Head of Communications',
            'remit' => 'Community services content budget',
            'is_active' => true,
        ], $overrides));
    }

    private function ask(array $requests, FundingApprover $approver)
    {
        return $this->post(route('admin.funding.rounds.store'), [
            'ids' => collect($requests)->pluck('id')->all(),
            'funding_approver_id' => $approver->id,
        ]);
    }

    public function test_one_ask_covers_the_whole_batch(): void
    {
        $one = $this->content('scoped', '8');
        $two = $this->content('awaiting_funding', '12.5');
        $approver = $this->approver();

        $this->loginAsAdmin();
        $this->ask([$one, $two], $approver)->assertRedirect();

        $round = FundingRound::firstOrFail();

        $this->assertSame(2, $round->items->count());
        $this->assertEquals(20.5, (float) $round->total_hours);

        // One email, not one per piece. That is the whole reason for the round.
        Mail::assertSent(FundingRequested::class, 1);
        Mail::assertSent(FundingRequested::class, fn ($m) => $m->hasTo('sam.okafor@example.com'));
    }

    public function test_the_ask_moves_everything_to_awaiting_funding(): void
    {
        $one = $this->content('suggested', '8');
        $two = $this->content('scoped', '4');

        $this->loginAsAdmin();
        $this->ask([$one, $two], $this->approver());

        $this->assertSame('awaiting_funding', $one->fresh()->status);
        $this->assertSame('awaiting_funding', $two->fresh()->status);
    }

    public function test_nobody_is_asked_to_fund_something_nobody_has_sized(): void
    {
        $sized = $this->content('scoped', '8');
        $unsized = $this->content('suggested', null);

        $this->loginAsAdmin();
        $this->ask([$sized, $unsized], $this->approver())
            ->assertSessionHas('error', fn ($e) => str_contains($e, $unsized->reference));

        // Nothing goes out at all: a partial ask is a different ask.
        $this->assertSame(0, FundingRound::count());
        Mail::assertNothingSent();
    }

    public function test_the_same_piece_is_not_asked_for_twice(): void
    {
        $request = $this->content();
        $approver = $this->approver();

        $this->loginAsAdmin();
        $this->ask([$request], $approver);
        Mail::fake();

        $this->ask([$request], $approver)
            ->assertSessionHas('error', fn ($e) => str_contains($e, $request->reference));

        $this->assertSame(1, FundingRound::count());
        Mail::assertNothingSent();
    }

    public function test_the_approver_sees_every_line_and_the_total(): void
    {
        $one = $this->content('scoped', '8');
        $two = $this->content('scoped', '12.5');

        $this->loginAsAdmin();
        $this->ask([$one, $two], $this->approver());

        $round = FundingRound::firstOrFail();

        $this->get(route('funding.show', $round->token))
            ->assertSuccessful()
            ->assertSee($one->reference)
            ->assertSee($two->reference)
            ->assertSee('20.5');
    }

    public function test_approving_releases_the_whole_batch(): void
    {
        $one = $this->content('scoped', '8');
        $two = $this->content('scoped', '4');

        $this->loginAsAdmin();
        $this->ask([$one, $two], $this->approver());
        $round = FundingRound::firstOrFail();

        $this->post(route('funding.respond', $round->token), ['decision' => 'approved'])
            ->assertSuccessful();

        $this->assertSame('in_progress', $one->fresh()->status);
        $this->assertSame('in_progress', $two->fresh()->status);
        $this->assertSame('approved', $round->fresh()->status);
    }

    public function test_declining_needs_a_reason_and_leaves_the_work_where_it_is(): void
    {
        $request = $this->content();

        $this->loginAsAdmin();
        $this->ask([$request], $this->approver());
        $round = FundingRound::firstOrFail();

        $this->post(route('funding.respond', $round->token), ['decision' => 'declined'])
            ->assertSessionHasErrors('notes');

        $this->post(route('funding.respond', $round->token), [
            'decision' => 'declined',
            'notes' => 'No budget until Q4.',
        ])->assertSuccessful();

        $this->assertSame('declined', $round->fresh()->status);
        $this->assertSame('No budget until Q4.', $round->fresh()->notes);
        // Still waiting, not written off.
        $this->assertSame('awaiting_funding', $request->fresh()->status);
    }

    public function test_the_link_stops_working_once_it_has_been_used(): void
    {
        $request = $this->content();

        $this->loginAsAdmin();
        $this->ask([$request], $this->approver());
        $round = FundingRound::firstOrFail();
        $token = $round->token;

        $this->post(route('funding.respond', $token), ['decision' => 'approved']);

        $this->assertNull($round->fresh()->token);
        $this->get(route('funding.show', $token))->assertNotFound();
        $this->post(route('funding.respond', $token), ['decision' => 'declined', 'notes' => 'x'])->assertNotFound();
    }

    public function test_the_approval_binds_to_the_hours_that_were_shown(): void
    {
        $request = $this->content('scoped', '8');

        $this->loginAsAdmin();
        $this->ask([$request], $this->approver());
        $round = FundingRound::firstOrFail();

        // Re-estimated after the ask went out.
        $request->update(['estimated_hours' => '40']);

        // What was agreed is what was on the page, not what the number says now.
        $this->assertEquals(8, (float) $round->fresh()->items->first()->estimated_hours);
        $this->assertEquals(8, (float) $round->fresh()->total_hours);
        $this->assertCount(1, $round->fresh()->driftedItems());
    }

    public function test_the_record_keeps_who_was_asked_after_the_list_changes(): void
    {
        $request = $this->content();
        $approver = $this->approver();

        $this->loginAsAdmin();
        $this->ask([$request], $approver);

        $approver->update(['name' => 'Someone Else', 'is_active' => false]);

        $this->assertStringContainsString('Sam Okafor', FundingRound::firstOrFail()->approver_name);
    }

    public function test_an_inactive_approver_cannot_be_asked(): void
    {
        $request = $this->content();
        $retired = $this->approver(['is_active' => false]);

        $this->loginAsAdmin();
        $this->ask([$request], $retired)->assertSessionHasErrors('funding_approver_id');

        $this->assertSame(0, FundingRound::count());
    }

    public function test_the_managed_list_is_administered_and_never_deleted(): void
    {
        $this->loginAsAdmin();

        $this->post(route('admin.funding-approvers.store'), [
            'name' => 'Budget Holder',
            'email' => 'budget@example.com',
            'job_title' => 'Director',
            'remit' => 'Everything',
            'is_active' => '1',
        ])->assertRedirect();

        $approver = FundingApprover::where('email', 'budget@example.com')->firstOrFail();

        $this->delete(route('admin.funding-approvers.destroy', $approver))->assertRedirect();

        // Decisions already made name this person.
        $this->assertDatabaseHas('funding_approvers', ['id' => $approver->id, 'is_active' => false]);
    }
}
