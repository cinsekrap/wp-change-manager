<?php

namespace Tests\Feature;

use App\Mail\ClarificationRequested;
use App\Mail\ClarificationResponded;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestItem;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClarificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function createChangeRequest(array $overrides = []): ChangeRequest
    {
        $site = Site::create([
            'name' => 'Test Site',
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        return ChangeRequest::create(array_merge([
            'reference' => 'WCR-20260720-001',
            'site_id' => $site->id,
            'page_url' => 'https://example.com/test',
            'page_title' => 'Test Page',
            'cpt_slug' => 'page',
            'status' => 'approved',
            'requester_name' => 'John Doe',
            'requester_email' => 'john@example.com',
        ], $overrides));
    }

    private function awaitingRequest(array $overrides = []): ChangeRequest
    {
        return $this->createChangeRequest(array_merge([
            'status' => 'awaiting_user',
            'previous_status' => 'approved',
            'clarification_message' => 'Which phone number is correct?',
            'clarification_requested_at' => now(),
            'sla_paused_at' => now(),
        ], $overrides));
    }

    // ---- Admin action ----

    public function test_admin_can_request_clarification(): void
    {
        $user = $this->loginAsAdmin();
        $cr = $this->createChangeRequest();

        $response = $this->post("/admin/requests/{$cr->id}/request-clarification", [
            'clarification_message' => 'Which phone number is correct?',
        ]);

        $response->assertRedirect();
        $cr->refresh();
        $this->assertEquals('awaiting_user', $cr->status);
        $this->assertEquals('approved', $cr->previous_status);
        $this->assertEquals('Which phone number is correct?', $cr->clarification_message);
        $this->assertNotNull($cr->clarification_requested_at);
        $this->assertNotNull($cr->sla_paused_at);
        $this->assertTrue($cr->slaStopped());

        $this->assertDatabaseHas('change_request_status_log', [
            'change_request_id' => $cr->id,
            'old_status' => 'approved',
            'new_status' => 'awaiting_user',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('change_request_notes', [
            'change_request_id' => $cr->id,
            'note' => 'Clarification requested: Which phone number is correct?',
        ]);

        Mail::assertSent(ClarificationRequested::class, function ($mail) use ($cr) {
            return $mail->hasTo($cr->requester_email);
        });
    }

    public function test_clarification_requires_a_message(): void
    {
        $this->loginAsAdmin();
        $cr = $this->createChangeRequest();

        $response = $this->post("/admin/requests/{$cr->id}/request-clarification", []);

        $response->assertSessionHasErrors('clarification_message');
        $this->assertEquals('approved', $cr->refresh()->status);
    }

    public function test_cannot_request_clarification_on_closed_request(): void
    {
        $this->loginAsAdmin();
        $cr = $this->createChangeRequest(['status' => 'done']);

        $response = $this->post("/admin/requests/{$cr->id}/request-clarification", [
            'clarification_message' => 'Too late to ask.',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('done', $cr->refresh()->status);
        Mail::assertNothingSent();
    }

    // ---- Public respond page ----

    public function test_respond_page_loads_with_valid_signature(): void
    {
        $cr = $this->awaitingRequest();

        $response = $this->get(URL::signedRoute('respond.show', ['reference' => $cr->reference]));

        $response->assertStatus(200);
        $response->assertSee('Which phone number is correct?');
        $response->assertSee($cr->reference);
    }

    public function test_respond_page_rejects_invalid_signature(): void
    {
        $cr = $this->awaitingRequest();

        $response = $this->get("/respond/{$cr->reference}");

        $response->assertRedirect(route('tracking'));
    }

    public function test_respond_page_shows_closed_state_when_not_awaiting(): void
    {
        $cr = $this->createChangeRequest();

        $response = $this->get(URL::signedRoute('respond.show', ['reference' => $cr->reference]));

        $response->assertStatus(200);
        $response->assertSee('No response needed');
    }

    public function test_responding_with_comment_reverts_status_and_notifies_assignee(): void
    {
        $assignee = User::factory()->create(['email' => 'assignee@example.com']);
        $cr = $this->awaitingRequest(['assigned_to' => $assignee->id, 'sla_paused_at' => now()->subWeek()]);

        $response = $this->post(URL::signedRoute('respond.store', ['reference' => $cr->reference]), [
            'comment' => 'The second number is correct.',
        ]);

        $response->assertStatus(200);
        $response->assertSee('response sent');

        $cr->refresh();
        $this->assertEquals('approved', $cr->status);
        $this->assertNull($cr->previous_status);
        $this->assertNull($cr->clarification_message);
        $this->assertNull($cr->clarification_requested_at);
        $this->assertNull($cr->sla_paused_at);
        // A week awaiting the user spans 5 weekdays = 40 business hours
        $this->assertEquals(40, $cr->sla_paused_hours);

        $this->assertDatabaseHas('change_request_status_log', [
            'change_request_id' => $cr->id,
            'old_status' => 'awaiting_user',
            'new_status' => 'approved',
            'user_id' => null,
        ]);
        $this->assertDatabaseHas('change_request_notes', [
            'change_request_id' => $cr->id,
            'user_id' => null,
            'note' => 'Requester response to clarification request: The second number is correct.',
        ]);

        Mail::assertSent(ClarificationResponded::class, function ($mail) {
            return $mail->hasTo('assignee@example.com');
        });
    }

    public function test_responding_can_edit_request_items(): void
    {
        $cr = $this->awaitingRequest();
        $item = ChangeRequestItem::create([
            'change_request_id' => $cr->id,
            'action_type' => 'change',
            'content_area' => 'Contact details',
            'description' => 'Change the phone number to 01234 567890',
            'sort_order' => 1,
        ]);

        $response = $this->post(URL::signedRoute('respond.store', ['reference' => $cr->reference]), [
            'items' => [$item->id => 'Change the phone number to 09876 543210'],
        ]);

        $response->assertStatus(200);
        $this->assertEquals('Change the phone number to 09876 543210', $item->refresh()->description);
        $this->assertEquals('approved', $cr->refresh()->status);
    }

    public function test_response_requires_a_comment_or_an_item_change(): void
    {
        $cr = $this->awaitingRequest();
        $item = ChangeRequestItem::create([
            'change_request_id' => $cr->id,
            'action_type' => 'change',
            'content_area' => 'Contact details',
            'description' => 'Original wording',
            'sort_order' => 1,
        ]);

        $response = $this->post(URL::signedRoute('respond.store', ['reference' => $cr->reference]), [
            'comment' => '',
            'items' => [$item->id => 'Original wording'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('comment');
        $this->assertEquals('awaiting_user', $cr->refresh()->status);
        Mail::assertNothingSent();
    }

    public function test_awaiting_user_request_is_not_over_sla(): void
    {
        $cr = $this->awaitingRequest([
            'priority' => 'urgent',
            'created_at' => now()->subWeeks(2),
        ]);

        $this->assertTrue($cr->slaStopped());
        $this->assertFalse($cr->isOverSla());
    }
}
