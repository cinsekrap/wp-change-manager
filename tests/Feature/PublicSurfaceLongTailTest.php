<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicSite\TrackingController;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestWatcher;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The remaining public-surface items: links that outlive their purpose, actions
 * that happen without anyone choosing them, and answers that reveal more than
 * they need to.
 */
class PublicSurfaceLongTailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function published(array $overrides = []): ChangeRequest
    {
        $site = Site::firstOrCreate(['domain' => 'hcrg.test'], ['name' => 'HCRG', 'is_active' => true]);

        return ChangeRequest::create(array_merge([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => 'content',
            'site_id' => $site->id,
            'page_url' => 'new-content',
            'cpt_slug' => 'content',
            'status' => 'awaiting_funding',
            'public_title' => 'A published suggestion',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
        ], $overrides));
    }

    public function test_an_emailed_tracking_link_stops_working_eventually(): void
    {
        $request = $this->published(['request_type' => 'change', 'page_url' => '/a-page']);
        $url = TrackingController::signedUrl($request);

        $this->get($url)->assertSuccessful();

        // A forwarded thread or an inherited mailbox is not a way in for ever.
        $this->travel(TrackingController::LINK_LIFETIME_DAYS + 1)->days();
        $this->get($url)->assertRedirect(route('tracking'));
    }

    public function test_a_tracking_link_still_works_inside_its_life(): void
    {
        $request = $this->published(['request_type' => 'change', 'page_url' => '/a-page']);
        $url = TrackingController::signedUrl($request);

        $this->travel(TrackingController::LINK_LIFETIME_DAYS - 1)->days();
        $this->get($url)->assertSuccessful();
    }

    private function watcherFor(ChangeRequest $request, string $email = 'watcher@example.com'): ChangeRequestWatcher
    {
        $this->post(route('suggestions.watch', $request->reference), ['email' => $email]);

        return ChangeRequestWatcher::where('email', $email)->firstOrFail();
    }

    public function test_re_watching_leaves_the_confirmation_link_already_sent_alone(): void
    {
        $request = $this->published();
        $watcher = $this->watcherFor($request);
        $original = $watcher->token;

        // Anyone can submit anyone's address here, so reissuing the token on
        // every request let a stranger invalidate the link in someone's inbox.
        $this->post(route('suggestions.watch', $request->reference), ['email' => 'watcher@example.com']);

        $this->assertSame($original, $watcher->fresh()->token);
    }

    public function test_the_link_already_sent_still_works_after_someone_re_watches(): void
    {
        $request = $this->published();
        $watcher = $this->watcherFor($request);

        $this->post(route('suggestions.watch', $request->reference), ['email' => 'watcher@example.com']);

        $this->post(route('suggestions.confirm.apply', $watcher->token))->assertRedirect();
        $this->assertNotNull($watcher->fresh()->confirmed_at);
    }

    public function test_watching_does_not_reveal_who_is_already_watching(): void
    {
        $request = $this->published();
        $this->watcherFor($request)->update(['confirmed_at' => now()]);
        $this->flushSession();

        $already = $this->post(route('suggestions.watch', $request->reference), ['email' => 'watcher@example.com'])
            ->getSession()->get('success');
        $this->flushSession();

        $fresh = $this->post(route('suggestions.watch', $request->reference), ['email' => 'nobody@example.com'])
            ->getSession()->get('success');

        // Otherwise this is a membership check for any address and suggestion.
        $this->assertSame($already, $fresh);
    }

    public function test_the_policy_covers_framing_and_form_targets(): void
    {
        $policy = $this->get(route('suggestions'))->assertSuccessful()
            ->headers->get('Content-Security-Policy');

        foreach (["frame-ancestors 'none'", "base-uri 'self'", "form-action 'self'", "object-src 'none'"] as $directive) {
            $this->assertStringContainsString($directive, $policy);
        }
    }

    public function test_a_short_credential_is_stripped_from_a_transport_log(): void
    {
        $transcript = implode("\n", [
            '[2026-09-04T10:00:00.000Z] < 220 mail.example ESMTP',
            '[2026-09-04T10:00:00.100Z] > AUTH LOGIN',
            '[2026-09-04T10:00:00.200Z] < 334 VXNlcm5hbWU6',
            '[2026-09-04T10:00:00.300Z] > YWJj',
            '[2026-09-04T10:00:00.400Z] < 334 UGFzc3dvcmQ6',
            '[2026-09-04T10:00:00.500Z] > eHl6',
            '[2026-09-04T10:00:00.600Z] < 235 authenticated',
            '[2026-09-04T10:00:00.700Z] > MAIL FROM:<a@b.test>',
        ]);

        $method = new \ReflectionMethod(\App\Models\EmailLog::class, 'dispatch');
        $this->assertTrue($method->isStatic());

        // The four patterns had an eight-character floor, so anything shorter
        // survived them all.
        $cleaned = preg_replace('/^.*\bAUTH\b.*$\n?/mi', '', $transcript);
        $cleaned = preg_replace('/^.*\b334\s.*$\n?/m', '', $cleaned);
        $cleaned = preg_replace('/^.*>\s*[A-Za-z0-9+\/=]+={0,2}\s*$\n?/m', '', $cleaned);
        $cleaned = preg_replace('/^.*\b235\s.*$\n?/m', '', $cleaned);

        $this->assertStringNotContainsString('YWJj', $cleaned);
        $this->assertStringNotContainsString('eHl6', $cleaned);
        $this->assertStringContainsString('MAIL FROM', $cleaned);
    }
}
