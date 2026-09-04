<?php

namespace Tests\Feature;

use App\Models\Site;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * The public limiters have to count something the caller cannot hand themselves.
 * A session id alone is issued fresh to anyone who keeps no cookie, so it counted
 * nothing; an IP alone would put ~3,000 colleagues in one bucket.
 */
class RateLimitKeyTest extends TestCase
{
    /** @return Limit[] */
    private function limitsFor(string $name): array
    {
        $request = request();
        $request->setLaravelSession($this->app['session']->driver());

        return (array) RateLimiter::limiter($name)($request);
    }

    private function addressLimit(string $name): ?Limit
    {
        return collect($this->limitsFor($name))
            ->first(fn (Limit $l) => str_contains($l->key, request()->ip()));
    }

    public function test_every_public_limiter_has_a_limit_the_caller_cannot_reset(): void
    {
        $this->startSession();

        foreach (['public-submit', 'public-api', 'public-tracking', 'public-upload'] as $name) {
            $limits = $this->limitsFor($name);

            // A key built from the session id is a bucket the caller issues
            // themselves: drop the cookie, get a new one. Something in the set
            // has to be outside their control.
            $this->assertNotNull($this->addressLimit($name),
                "'{$name}' has no per-address limit, so discarding the cookie resets it");

            $this->assertTrue(
                collect($limits)->contains(fn (Limit $l) => str_contains($l->key, session()->getId())),
                "'{$name}' has no per-session limit, so one shared office would share a bucket"
            );
        }
    }

    public function test_a_new_session_does_not_reset_the_address_budget(): void
    {
        $this->startSession();
        $before = $this->addressLimit('public-tracking')->key;

        // Exactly what a caller who keeps no cookie gets on every request.
        $this->app['session']->driver()->regenerate(true);
        $after = $this->addressLimit('public-tracking')->key;

        $this->assertSame($before, $after,
            'A fresh session moved the per-address bucket, so it is not a backstop at all.');
    }

    public function test_the_address_backstop_is_loose_enough_for_a_shared_office(): void
    {
        $this->startSession();

        // ~3,000 colleagues share one address. The backstop must catch bulk abuse
        // without catching them, so it has to sit well above the per-person limit.
        foreach (['public-submit', 'public-api', 'public-tracking', 'public-upload'] as $name) {
            $session = collect($this->limitsFor($name))
                ->first(fn (Limit $l) => str_contains($l->key, session()->getId()));

            $this->assertGreaterThan($session->maxAttempts, $this->addressLimit($name)->maxAttempts,
                "'{$name}' allows an address no more than a single person");
        }
    }

    public function test_uploads_have_a_tighter_budget_than_the_general_api(): void
    {
        $this->startSession();

        $perSession = fn (string $name) => collect($this->limitsFor($name))
            ->first(fn (Limit $l) => str_contains($l->key, session()->getId()))->maxAttempts;

        // 60/minute is far too generous for an endpoint that accepts 128MB files.
        $this->assertLessThan($perSession('public-api'), $perSession('public-upload'));
    }

    public function test_the_suggestion_search_is_throttled_at_all(): void
    {
        $route = collect(app('router')->getRoutes())
            ->first(fn ($r) => $r->getName() === 'suggestions.search');

        $this->assertContains('throttle:public-api', $route->gatherMiddleware(),
            'The public suggestion search runs an unindexable LIKE with no limit.');
    }

    public function test_a_fresh_sitemap_is_not_re_crawled_on_demand(): void
    {
        $site = Site::create([
            'name' => 'HCRG', 'domain' => 'hcrg.test', 'is_active' => true,
            'sitemap_url' => 'https://hcrg.test/sitemap.xml',
            'sitemap_refreshed_at' => now()->subMinutes(5),
        ]);

        // Refreshing rewrites every page row for the site; the wizard checks
        // staleness first, and the server must not depend on it having done so.
        $this->postJson(route('api.sitemap.refresh', $site))
            ->assertSuccessful()
            ->assertJson(['skipped' => true]);
    }

    public function test_a_stale_sitemap_is_still_refreshed(): void
    {
        $site = Site::create([
            'name' => 'HCRG', 'domain' => 'hcrg.test', 'is_active' => true,
            'sitemap_refreshed_at' => now()->subDays(2),
        ]);

        // Not asserting a successful crawl — there is no sitemap to fetch in a
        // test — only that staleness lets it through to try.
        $this->postJson(route('api.sitemap.refresh', $site))
            ->assertSuccessful()
            ->assertJsonMissing(['skipped' => true]);
    }
}
