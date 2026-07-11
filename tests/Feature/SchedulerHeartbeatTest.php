<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SchedulerHeartbeatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        // Use the database store (like production) rather than the array
        // store, so values genuinely round-trip through serialization —
        // caching a non-scalar heartbeat broke only on real stores.
        config(['cache.default' => 'database']);
    }

    public function test_schedule_run_records_a_scalar_heartbeat(): void
    {
        $this->assertNull(Cache::get('scheduler_last_run'));

        $this->artisan('schedule:run');

        $this->assertIsNumeric(Cache::get('scheduler_last_run'));
    }

    public function test_dashboard_shows_running_when_heartbeat_is_fresh(): void
    {
        $this->loginAsAdmin();
        Cache::put('scheduler_last_run', now()->subMinute()->timestamp);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Scheduler running')
            ->assertDontSee('Scheduled tasks are not running.');
    }

    public function test_dashboard_warns_when_heartbeat_is_stale(): void
    {
        $this->loginAsAdmin();
        Cache::put('scheduler_last_run', now()->subHours(2)->timestamp);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Scheduler not running')
            ->assertSee('Scheduled tasks are not running.')
            ->assertSee('2 hours ago');
    }

    public function test_dashboard_warns_when_no_heartbeat_exists(): void
    {
        $this->loginAsAdmin();

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Scheduler not running')
            ->assertSee('No heartbeat has ever been recorded.');
    }

    public function test_dashboard_survives_a_legacy_object_heartbeat(): void
    {
        // v1.8.1 cached a Carbon object; cache stores refuse to unserialize
        // non-allowlisted classes, which 500'd the dashboard. Any non-numeric
        // value must render as "not running", never break the page.
        $this->loginAsAdmin();
        Cache::put('scheduler_last_run', now());

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Scheduler not running');
    }
}
