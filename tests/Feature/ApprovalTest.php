<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestStatusLog;
use App\Models\Site;
use Tests\TestCase;

class ApprovalTest extends TestCase
{
    private function createApproverWithRequest(array $approverOverrides = [], array $crOverrides = []): array
    {
        $site = Site::create([
            'name' => 'Approval Site',
            'domain' => 'approval.com',
            'is_active' => true,
        ]);

        $cr = ChangeRequest::create(array_merge([
            'reference' => 'WCR-20260327-001',
            'site_id' => $site->id,
            'page_url' => 'https://approval.com/page',
            'cpt_slug' => 'page',
            'status' => 'referred',
            'requester_name' => 'Requester',
            'requester_email' => 'requester@example.com',
        ], $crOverrides));

        $approver = ChangeRequestApprover::create(array_merge([
            'change_request_id' => $cr->id,
            'name' => 'Approver Person',
            'email' => 'approver@example.com',
            'status' => 'pending',
            'token' => 'valid-test-token-abc123',
        ], $approverOverrides));

        return [$cr, $approver];
    }

    public function test_approval_page_loads_with_valid_token(): void
    {
        [$cr, $approver] = $this->createApproverWithRequest();

        $response = $this->get("/approve/{$approver->token}");

        $response->assertStatus(200);
    }

    public function test_approval_page_404s_with_invalid_token(): void
    {
        $response = $this->get('/approve/fake-invalid-token');

        $response->assertStatus(404);
    }

    public function test_can_approve_request(): void
    {
        [$cr, $approver] = $this->createApproverWithRequest();

        $response = $this->post("/approve/{$approver->token}", [
            'status' => 'approved',
            'notes' => 'Looks good to me.',
        ]);

        $response->assertStatus(200);

        $approver->refresh();
        $this->assertEquals('approved', $approver->status);
        $this->assertNull($approver->token);
        $this->assertNotNull($approver->responded_at);
    }

    public function test_can_reject_request(): void
    {
        [$cr, $approver] = $this->createApproverWithRequest();

        $response = $this->post("/approve/{$approver->token}", [
            'status' => 'rejected',
            'notes' => 'Needs more detail on section 3.',
        ]);

        $response->assertStatus(200);

        $approver->refresh();
        $this->assertEquals('rejected', $approver->status);
        $this->assertEquals('Needs more detail on section 3.', $approver->notes);
    }

    public function test_auto_advances_to_approved_when_all_approve(): void
    {
        $site = Site::create([
            'name' => 'Approval Site',
            'domain' => 'approval.com',
            'is_active' => true,
        ]);

        $cr = ChangeRequest::create([
            'reference' => 'WCR-20260327-001',
            'site_id' => $site->id,
            'page_url' => 'https://approval.com/page',
            'cpt_slug' => 'page',
            'status' => 'referred',
            'requester_name' => 'Requester',
            'requester_email' => 'requester@example.com',
        ]);

        $approver1 = ChangeRequestApprover::create([
            'change_request_id' => $cr->id,
            'name' => 'Approver One',
            'email' => 'one@example.com',
            'status' => 'pending',
            'token' => 'token-one-abc',
        ]);

        $approver2 = ChangeRequestApprover::create([
            'change_request_id' => $cr->id,
            'name' => 'Approver Two',
            'email' => 'two@example.com',
            'status' => 'pending',
            'token' => 'token-two-abc',
        ]);

        // First approver approves
        $this->post("/approve/{$approver1->token}", [
            'status' => 'approved',
        ]);

        $cr->refresh();
        $this->assertEquals('referred', $cr->status);

        // Second approver approves
        $this->post("/approve/{$approver2->token}", [
            'status' => 'approved',
        ]);

        $cr->refresh();
        $this->assertEquals('approved', $cr->status);

        // Verify a status log was created for the auto-advance
        $this->assertDatabaseHas('change_request_status_log', [
            'change_request_id' => $cr->id,
            'old_status' => 'referred',
            'new_status' => 'approved',
        ]);
    }

    public function test_used_token_returns_404(): void
    {
        [$cr, $approver] = $this->createApproverWithRequest();

        // Use the token
        $this->post("/approve/{$approver->token}", [
            'status' => 'approved',
        ]);

        // Try to access the same token again
        $response = $this->get('/approve/valid-test-token-abc123');

        $response->assertStatus(404);
    }
}
