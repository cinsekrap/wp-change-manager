<?php

namespace Tests\Feature;

use App\Mail\RequestChase;
use App\Models\ChangeRequest;
use App\Models\Setting;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ChaseStaleRequestsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Setting::set('chase_enabled', '1');
        Setting::set('chase_hours', '48');
    }

    private function staleAssignedRequest(User $assignee): ChangeRequest
    {
        $site = Site::create([
            'name' => 'Test Site',
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        $cr = ChangeRequest::create([
            'reference' => 'WCR-20260327-001',
            'site_id' => $site->id,
            'page_url' => 'https://example.com/test',
            'cpt_slug' => 'page',
            'status' => 'referred',
            'assigned_to' => $assignee->id,
            'requester_name' => 'John Doe',
            'requester_email' => 'john@example.com',
        ]);

        // Force it past the inactivity cutoff without touching timestamps via the model.
        DB::table('change_requests')->where('id', $cr->id)->update(['updated_at' => now()->subHours(72)]);

        return $cr->refresh();
    }

    public function test_chase_creates_a_system_note_without_a_user(): void
    {
        $assignee = User::factory()->create(['email' => 'assignee@example.com']);
        $cr = $this->staleAssignedRequest($assignee);

        $this->artisan('requests:chase')->assertExitCode(0);

        Mail::assertSent(RequestChase::class);
        // The note is written with a null user_id — this previously violated a
        // NOT NULL constraint and would have errored.
        $this->assertDatabaseHas('change_request_notes', [
            'change_request_id' => $cr->id,
            'user_id' => null,
            'note' => 'Automated chase reminder sent',
        ]);
    }

    public function test_request_page_renders_system_note(): void
    {
        $assignee = User::factory()->create();
        $cr = $this->staleAssignedRequest($assignee);

        $this->artisan('requests:chase');

        $this->loginAsAdmin();
        $response = $this->get("/admin/requests/{$cr->id}");

        $response->assertOk();
        $response->assertSee('System');
    }
}
