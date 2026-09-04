<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * A reference plus an email returns the whole request. References run in a
 * readable per-day sequence, so the address is the only part that is hard to
 * arrive at — which makes repeated failures worth counting.
 */
class TrackingLockoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function request(): ChangeRequest
    {
        $site = Site::firstOrCreate(['domain' => 'hcrg.test'], ['name' => 'HCRG', 'is_active' => true]);

        return ChangeRequest::create([
            'reference' => 'WCR-20260904-001',
            'site_id' => $site->id,
            'page_url' => '/a-page',
            'page_title' => 'A page',
            'cpt_slug' => 'pages',
            'status' => 'requested',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
        ]);
    }

    private function lookUp(string $reference, string $email = 'jane@example.com')
    {
        return $this->post('/track', ['reference' => $reference, 'email' => $email]);
    }

    public function test_working_through_references_is_eventually_refused(): void
    {
        $this->request();

        for ($i = 1; $i <= 10; $i++) {
            $this->lookUp(sprintf('WCR-20260904-%03d', $i + 100))
                ->assertSessionHas('error', 'No request found with that reference and email combination.');
        }

        $this->lookUp('WCR-20260904-999')
            ->assertSessionHas('error', fn ($error) => str_contains($error, 'Too many attempts'));
    }

    public function test_the_real_reference_stops_working_too_once_refused(): void
    {
        $this->request();

        for ($i = 1; $i <= 11; $i++) {
            $this->lookUp(sprintf('WCR-20260904-%03d', $i + 100));
        }

        // Otherwise the lockout is a free oracle: keep guessing until one works.
        $this->lookUp('WCR-20260904-001')
            ->assertSessionHas('error', fn ($error) => str_contains($error, 'Too many attempts'));
    }

    public function test_a_mistyped_reference_does_not_cost_the_person_their_next_hour(): void
    {
        $this->request();

        $this->lookUp('WCR-20260904-002');
        $this->lookUp('WCR-20260904-001')->assertSessionMissing('error');

        // The budget resets on success, so the next genuine slip starts fresh.
        for ($i = 1; $i <= 10; $i++) {
            $this->lookUp(sprintf('WCR-20260904-%03d', $i + 100));
        }
        $this->lookUp('WCR-20260904-999')
            ->assertSessionHas('error', fn ($error) => str_contains($error, 'Too many attempts'));
    }

    public function test_one_persons_failures_do_not_lock_out_a_colleague(): void
    {
        $this->request();

        // Same office, same IP. An IP-keyed lockout would take tracking away
        // from ~3,000 people because one of them fumbled a reference.
        for ($i = 1; $i <= 11; $i++) {
            $this->lookUp(sprintf('WCR-20260904-%03d', $i + 100), 'someone.else@example.com');
        }

        $this->lookUp('WCR-20260904-001')->assertSessionMissing('error');
    }

    public function test_a_successful_lookup_still_returns_the_request(): void
    {
        $this->request();

        $this->lookUp('WCR-20260904-001')
            ->assertSuccessful()
            ->assertSee('WCR-20260904-001');
    }

    public function test_the_refusal_does_not_say_which_half_was_wrong(): void
    {
        $this->request();

        $wrongReference = $this->lookUp('WCR-20260904-777');
        $this->flushSession();
        $wrongEmail = $this->lookUp('WCR-20260904-001', 'nobody@example.com');

        $this->assertSame(
            $wrongReference->getSession()->get('error'),
            $wrongEmail->getSession()->get('error')
        );
    }
}
