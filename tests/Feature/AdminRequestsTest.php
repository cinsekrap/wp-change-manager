<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestNote;
use App\Models\ChangeRequestStatusLog;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminRequestsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function createChangeRequest(array $siteOverrides = [], array $crOverrides = []): ChangeRequest
    {
        $site = Site::create(array_merge([
            'name' => 'Test Site',
            'domain' => 'example.com',
            'is_active' => true,
        ], $siteOverrides));

        return ChangeRequest::create(array_merge([
            'reference' => 'WCR-20260327-001',
            'site_id' => $site->id,
            'page_url' => 'https://example.com/test',
            'cpt_slug' => 'page',
            'status' => 'requested',
            'requester_name' => 'John Doe',
            'requester_email' => 'john@example.com',
        ], $crOverrides));
    }

    public function test_request_list_loads(): void
    {
        $this->loginAsAdmin();

        $response = $this->get('/admin/requests');

        $response->assertStatus(200);
    }

    public function test_request_detail_loads(): void
    {
        $this->loginAsAdmin();
        $cr = $this->createChangeRequest();

        $response = $this->get("/admin/requests/{$cr->id}");

        $response->assertStatus(200);
    }

    public function test_can_update_status(): void
    {
        $user = $this->loginAsAdmin();
        $cr = $this->createChangeRequest();
        $date = now()->addDays(7)->format('Y-m-d');

        $response = $this->patch("/admin/requests/{$cr->id}/status", [
            'status' => 'scheduled',
            'scheduled_date' => $date,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('change_requests', [
            'id' => $cr->id,
            'status' => 'scheduled',
        ]);
        $this->assertEquals($date, $cr->refresh()->scheduled_date->format('Y-m-d'));
        $this->assertDatabaseHas('change_request_status_log', [
            'change_request_id' => $cr->id,
            'old_status' => 'requested',
            'new_status' => 'scheduled',
            'user_id' => $user->id,
        ]);
    }

    public function test_scheduling_requires_a_date(): void
    {
        $this->loginAsAdmin();
        $cr = $this->createChangeRequest();

        $response = $this->patch("/admin/requests/{$cr->id}/status", [
            'status' => 'scheduled',
        ]);

        $response->assertSessionHasErrors('scheduled_date');
        $this->assertDatabaseHas('change_requests', [
            'id' => $cr->id,
            'status' => 'requested',
        ]);
    }

    public function test_scheduled_request_is_not_over_sla(): void
    {
        // An urgent request created two weeks ago would normally be well overdue.
        $cr = $this->createChangeRequest([], [
            'priority' => 'urgent',
            'status' => 'scheduled',
            'scheduled_date' => now()->addDays(7)->format('Y-m-d'),
            'created_at' => now()->subWeeks(2),
        ]);

        $this->assertTrue($cr->slaStopped());
        $this->assertFalse($cr->isOverSla());
    }

    public function test_scheduled_date_cleared_when_status_changes(): void
    {
        $this->loginAsAdmin();
        $cr = $this->createChangeRequest([], [
            'status' => 'scheduled',
            'scheduled_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $response = $this->patch("/admin/requests/{$cr->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertRedirect();
        $cr->refresh();
        $this->assertEquals('approved', $cr->status);
        $this->assertNull($cr->scheduled_date);
    }

    public function test_cannot_move_past_referred_without_approvals(): void
    {
        $this->loginAsAdmin();
        $cr = $this->createChangeRequest([], ['status' => 'referred']);

        ChangeRequestApprover::create([
            'change_request_id' => $cr->id,
            'name' => 'Pending Approver',
            'email' => 'approver@example.com',
            'status' => 'pending',
        ]);

        $response = $this->patch("/admin/requests/{$cr->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('change_requests', [
            'id' => $cr->id,
            'status' => 'referred',
        ]);
    }

    public function test_can_add_note(): void
    {
        $user = $this->loginAsAdmin();
        $cr = $this->createChangeRequest();

        $response = $this->post("/admin/requests/{$cr->id}/notes", [
            'note' => 'This is a test note.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('change_request_notes', [
            'change_request_id' => $cr->id,
            'user_id' => $user->id,
            'note' => 'This is a test note.',
        ]);
    }

    public function test_can_add_approver(): void
    {
        $this->loginAsAdmin();
        $cr = $this->createChangeRequest();

        $response = $this->post("/admin/requests/{$cr->id}/approvers", [
            'name' => 'New Approver',
            'email' => 'newapprover@example.com',
        ]);

        $response->assertRedirect();

        $approver = ChangeRequestApprover::where('change_request_id', $cr->id)->first();
        $this->assertNotNull($approver);
        $this->assertEquals('New Approver', $approver->name);
        $this->assertNotNull($approver->token);
        $this->assertEquals('pending', $approver->status);
    }

    public function test_send_for_approval_works(): void
    {
        $this->loginAsAdmin();

        $site = Site::create([
            'name' => 'Approval Site',
            'domain' => 'approval.com',
            'is_active' => true,
            'default_approvers' => [
                ['name' => 'Default Approver', 'email' => 'default@example.com'],
            ],
        ]);

        $cr = ChangeRequest::create([
            'reference' => 'WCR-20260327-002',
            'site_id' => $site->id,
            'page_url' => 'https://approval.com/page',
            'cpt_slug' => 'page',
            'status' => 'requested',
            'requester_name' => 'Submitter',
            'requester_email' => 'submitter@example.com',
        ]);

        $response = $this->post("/admin/requests/{$cr->id}/send-for-approval");

        $response->assertRedirect();
        $cr->refresh();

        $this->assertEquals('referred', $cr->status);
        $this->assertCount(1, $cr->approvers);
        $this->assertEquals('Default Approver', $cr->approvers->first()->name);
    }
}
