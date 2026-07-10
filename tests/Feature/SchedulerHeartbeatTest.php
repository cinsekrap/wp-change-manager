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
    }

    public function test_schedule_run_records_a_heartbeat(): void
    {
        $this->assertNull(Cache::get('scheduler_last_run'));

        $this->artisan('schedule:run');

        $this->assertNotNull(Cache::get('scheduler_last_run'));
    }

    public function test_dashboard_shows_running_when_heartbeat_is_fresh(): void
    {
        $this->loginAsAdmin();
        Cache::put('scheduler_last_run', now()->subMinute());

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Scheduler running')
            ->assertDontSee('Scheduled tasks are not running.');
    }

    public function test_dashboard_warns_when_heartbeat_is_stale(): void
    {
        $this->loginAsAdmin();
        Cache::put('scheduler_last_run', now()->subHours(2));

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
}
