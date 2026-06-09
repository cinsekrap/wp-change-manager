<?php

namespace Tests\Feature;

use App\Mail\ScheduledForActionToday;
use App\Models\ChangeRequest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ScheduledTodayReminderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function scheduledRequest(array $overrides = []): ChangeRequest
    {
        $site = Site::create([
            'name' => 'Test Site',
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        return ChangeRequest::create(array_merge([
            'reference' => 'WCR-20260327-001',
            'site_id' => $site->id,
            'page_url' => 'https://example.com/test',
            'cpt_slug' => 'page',
            'status' => 'scheduled',
            'scheduled_date' => today(),
            'requester_name' => 'John Doe',
            'requester_email' => 'john@example.com',
        ], $overrides));
    }

    public function test_emails_assignee_for_request_scheduled_today(): void
    {
        $assignee = User::factory()->create(['email' => 'admin@example.com']);
        $cr = $this->scheduledRequest(['assigned_to' => $assignee->id]);

        $this->artisan('requests:notify-scheduled-today')->assertExitCode(0);

        Mail::assertSent(ScheduledForActionToday::class, fn ($mail) => $mail->hasTo('admin@example.com'));
        $this->assertDatabaseHas('email_logs', [
            'change_request_id' => $cr->id,
            'mailable_class' => 'ScheduledForActionToday',
            'recipient_email' => 'admin@example.com',
        ]);
    }

    public function test_skips_request_with_no_assignee(): void
    {
        $this->scheduledRequest();

        $this->artisan('requests:notify-scheduled-today')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_skips_request_scheduled_for_another_day(): void
    {
        $assignee = User::factory()->create();
        $this->scheduledRequest([
            'assigned_to' => $assignee->id,
            'scheduled_date' => today()->addDays(3),
        ]);

        $this->artisan('requests:notify-scheduled-today')->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_does_not_send_twice_in_one_day(): void
    {
        $assignee = User::factory()->create();
        $this->scheduledRequest(['assigned_to' => $assignee->id]);

        $this->artisan('requests:notify-scheduled-today');
        $this->artisan('requests:notify-scheduled-today');

        Mail::assertSent(ScheduledForActionToday::class, 1);
    }
}
