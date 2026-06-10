<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestItem;
use App\Models\CptType;
use App\Models\Setting;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TrainingFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function accessRequest(array $crOverrides = [], array $cptOverrides = []): ChangeRequest
    {
        $site = Site::create([
            'name' => 'Training Site',
            'domain' => 'training.example',
            'is_active' => true,
        ]);

        CptType::create(array_merge([
            'slug' => 'events',
            'name' => 'Events',
            'request_mode' => 'self_service',
            'training_url' => 'https://example.com/training-video',
        ], $cptOverrides));

        $cr = ChangeRequest::create(array_merge([
            'reference' => 'WCR-20260610-001',
            'request_type' => 'access',
            'site_id' => $site->id,
            'page_url' => 'self-service-access-request',
            'cpt_slug' => 'events',
            'status' => 'referred',
            'requester_name' => 'Requester',
            'requester_email' => 'requester@example.com',
            'access_recipient_name' => 'Alex Recipient',
            'access_recipient_email' => 'alex@example.com',
        ], $crOverrides));

        ChangeRequestItem::create([
            'change_request_id' => $cr->id,
            'action_type' => 'access_request',
            'content_area' => 'Access Request',
            'description' => 'I need to manage events.',
            'sort_order' => 0,
        ]);

        return $cr;
    }

    private function pendingApprover(ChangeRequest $cr, string $token = 'approver-token-abc'): ChangeRequestApprover
    {
        return ChangeRequestApprover::create([
            'change_request_id' => $cr->id,
            'name' => 'Approver Person',
            'email' => 'approver@example.com',
            'status' => 'pending',
            'token' => $token,
        ]);
    }

    public function test_approval_page_shows_access_details_not_fake_page(): void
    {
        $cr = $this->accessRequest();
        $approver = $this->pendingApprover($cr);

        $response = $this->get("/approve/{$approver->token}");

        $response->assertStatus(200);
        $response->assertSee('Access Request Approval');
        $response->assertSee('Events');
        $response->assertSee('Alex Recipient');
        $response->assertDontSee('self-service-access-request');
    }

    public function test_final_public_approval_sends_training_email_and_moves_to_training(): void
    {
        $cr = $this->accessRequest();
        $approver = $this->pendingApprover($cr);

        $this->post("/approve/{$approver->token}", ['status' => 'approved'])->assertStatus(200);

        $cr->refresh();
        $this->assertEquals('training', $cr->status);
        $this->assertNotNull($cr->training_token);
        $this->assertNotNull($cr->training_sent_at);

        $this->assertDatabaseHas('email_logs', [
            'mailable_class' => 'TrainingRequested',
            'recipient_email' => 'alex@example.com',
            'change_request_id' => $cr->id,
        ]);
        $this->assertDatabaseHas('change_request_status_log', [
            'change_request_id' => $cr->id,
            'old_status' => 'approved',
            'new_status' => 'training',
        ]);
    }

    public function test_stays_at_approved_when_no_training_url_configured(): void
    {
        $cr = $this->accessRequest([], ['training_url' => null]);
        $approver = $this->pendingApprover($cr);

        $this->post("/approve/{$approver->token}", ['status' => 'approved']);

        $cr->refresh();
        $this->assertEquals('approved', $cr->status);
        $this->assertDatabaseMissing('email_logs', ['mailable_class' => 'TrainingRequested']);
        $this->assertDatabaseHas('change_request_notes', [
            'change_request_id' => $cr->id,
        ]);
        $this->assertStringContainsString('Training email not sent', $cr->notes()->latest()->first()->note);
    }

    public function test_training_page_loads_with_valid_token(): void
    {
        $cr = $this->accessRequest([
            'status' => 'training',
            'training_token' => 'valid-training-token',
            'training_sent_at' => now(),
        ]);

        $response = $this->get('/training/valid-training-token');

        $response->assertStatus(200);
        $response->assertSee('https://example.com/training-video');
        $response->assertSee('Alex Recipient');
    }

    public function test_training_page_404s_with_invalid_token(): void
    {
        $this->get('/training/invalid-token')->assertStatus(404);
    }

    public function test_confirming_training_moves_request_to_trained_and_notifies(): void
    {
        Setting::set('new_request_alert_email', 'admin@example.com');

        $cr = $this->accessRequest([
            'status' => 'training',
            'training_token' => 'valid-training-token',
            'training_sent_at' => now(),
        ]);

        $response = $this->post('/training/valid-training-token', ['confirmed' => '1']);

        $response->assertStatus(200);

        $cr->refresh();
        $this->assertEquals('trained', $cr->status);
        $this->assertNotNull($cr->training_confirmed_at);

        $this->assertDatabaseHas('email_logs', [
            'mailable_class' => 'TrainingConfirmed',
            'recipient_email' => 'admin@example.com',
            'change_request_id' => $cr->id,
        ]);
        $this->assertDatabaseHas('change_request_status_log', [
            'change_request_id' => $cr->id,
            'old_status' => 'training',
            'new_status' => 'trained',
        ]);
    }

    public function test_confirmation_requires_the_checkbox(): void
    {
        $cr = $this->accessRequest([
            'status' => 'training',
            'training_token' => 'valid-training-token',
        ]);

        $response = $this->post('/training/valid-training-token', []);

        $response->assertSessionHasErrors('confirmed');
        $this->assertEquals('training', $cr->fresh()->status);
    }

    public function test_second_confirmation_does_not_double_record(): void
    {
        Setting::set('new_request_alert_email', 'admin@example.com');

        $cr = $this->accessRequest([
            'status' => 'training',
            'training_token' => 'valid-training-token',
        ]);

        $this->post('/training/valid-training-token', ['confirmed' => '1']);
        $firstConfirmedAt = $cr->fresh()->training_confirmed_at;

        $this->post('/training/valid-training-token', ['confirmed' => '1'])->assertStatus(200);

        $this->assertEquals($firstConfirmedAt, $cr->fresh()->training_confirmed_at);
        $this->assertEquals(1, $cr->emailLogs()->where('mailable_class', 'TrainingConfirmed')->count());
    }

    public function test_closed_request_shows_closed_page_instead_of_form(): void
    {
        $this->accessRequest([
            'status' => 'declined',
            'training_token' => 'valid-training-token',
        ]);

        $response = $this->get('/training/valid-training-token');

        $response->assertStatus(200);
        $response->assertSee('Closed');
    }

    public function test_admin_manual_approval_triggers_training(): void
    {
        $this->loginAsAdmin();
        $cr = $this->accessRequest();

        $this->patch("/admin/requests/{$cr->id}/status", ['status' => 'approved']);

        $cr->refresh();
        $this->assertEquals('training', $cr->status);
        $this->assertDatabaseHas('email_logs', ['mailable_class' => 'TrainingRequested']);
    }

    public function test_approval_override_triggers_training(): void
    {
        $this->loginAsAdmin();
        $cr = $this->accessRequest();
        $this->pendingApprover($cr);

        $this->post("/admin/requests/{$cr->id}/override-approvals");

        $cr->refresh();
        $this->assertEquals('training', $cr->status);
        $this->assertDatabaseHas('email_logs', ['mailable_class' => 'TrainingRequested']);
    }

    public function test_admin_can_resend_training_email(): void
    {
        $this->loginAsAdmin();
        $cr = $this->accessRequest([
            'status' => 'training',
            'training_token' => 'existing-token',
            'training_sent_at' => now()->subDay(),
        ]);

        $response = $this->post("/admin/requests/{$cr->id}/training/send");

        $response->assertSessionHas('success');
        $this->assertEquals('existing-token', $cr->fresh()->training_token);
        $this->assertDatabaseHas('email_logs', [
            'mailable_class' => 'TrainingRequested',
            'recipient_email' => 'alex@example.com',
        ]);
    }

    public function test_access_request_cannot_be_scheduled(): void
    {
        $this->loginAsAdmin();
        $cr = $this->accessRequest(['status' => 'approved']);

        $response = $this->patch("/admin/requests/{$cr->id}/status", [
            'status' => 'scheduled',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('approved', $cr->fresh()->status);
    }

    public function test_change_request_cannot_be_set_to_training(): void
    {
        $this->loginAsAdmin();
        $site = Site::create(['name' => 'Normal Site', 'domain' => 'normal.example', 'is_active' => true]);
        $cr = ChangeRequest::create([
            'reference' => 'WCR-20260610-002',
            'site_id' => $site->id,
            'page_url' => 'https://normal.example/page',
            'cpt_slug' => 'page',
            'status' => 'approved',
            'requester_name' => 'Requester',
            'requester_email' => 'requester@example.com',
        ]);

        $response = $this->patch("/admin/requests/{$cr->id}/status", ['status' => 'training']);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('approved', $cr->fresh()->status);
    }

    public function test_admin_detail_page_renders_access_ticket(): void
    {
        $this->loginAsAdmin();
        $cr = $this->accessRequest([
            'status' => 'training',
            'training_token' => 'valid-training-token',
            'training_sent_at' => now(),
        ]);

        $response = $this->get("/admin/requests/{$cr->id}");

        $response->assertStatus(200);
        $response->assertSee('Access for:');
        $response->assertSee('Alex Recipient');
        $response->assertSee('Training');
        $response->assertSee('Resend training email');
        $response->assertDontSee('Page:');
    }

    public function test_bulk_status_update_skips_inapplicable_statuses(): void
    {
        $this->loginAsAdmin();
        $cr = $this->accessRequest(['status' => 'approved']);

        $response = $this->postJson('/admin/requests/bulk/status', [
            'ids' => [$cr->id],
            'status' => 'scheduled',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['updated' => 0, 'skipped' => 1]);
        $this->assertEquals('approved', $cr->fresh()->status);
    }
}
