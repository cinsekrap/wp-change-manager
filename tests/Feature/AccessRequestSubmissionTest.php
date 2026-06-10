<?php

namespace Tests\Feature;

use App\Models\CptType;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccessRequestSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function validAccessRequestData(array $overrides = []): array
    {
        $site = Site::create([
            'name' => 'Test Site',
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        CptType::create([
            'slug' => 'events',
            'name' => 'Events',
            'request_mode' => 'self_service',
            'training_url' => 'https://example.com/training-video',
        ]);

        return array_merge([
            'site_id' => $site->id,
            'page_url' => 'self-service-access-request',
            'page_title' => null,
            'cpt_slug' => 'events',
            'is_new_page' => false,
            'request_type' => 'access',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'requester_phone' => null,
            'requester_role' => null,
            'access_recipient_name' => 'Alex Recipient',
            'access_recipient_email' => 'alex@example.com',
            'priority' => 'normal',
            'check_answers' => [],
            'items' => [
                [
                    'action_type' => 'access_request',
                    'content_area' => 'Access Request',
                    'description' => 'I need to manage events for my service.',
                ],
            ],
        ], $overrides);
    }

    public function test_can_submit_access_request_without_selecting_a_page(): void
    {
        $response = $this->postJson('/submit', $this->validAccessRequestData());

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'reference', 'redirect']);

        $this->assertDatabaseHas('change_requests', [
            'request_type' => 'access',
            'cpt_slug' => 'events',
            'access_recipient_name' => 'Alex Recipient',
            'access_recipient_email' => 'alex@example.com',
            'status' => 'requested',
        ]);
        $this->assertDatabaseHas('change_request_items', [
            'action_type' => 'access_request',
        ]);
    }

    public function test_access_request_requires_recipient_details(): void
    {
        $data = $this->validAccessRequestData([
            'access_recipient_name' => null,
            'access_recipient_email' => null,
        ]);

        $response = $this->postJson('/submit', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['access_recipient_name', 'access_recipient_email']);
    }

    public function test_access_request_rejected_for_non_self_service_cpt(): void
    {
        CptType::create([
            'slug' => 'page',
            'name' => 'Pages',
            'request_mode' => 'normal',
        ]);

        $data = $this->validAccessRequestData(['cpt_slug' => 'page']);

        $response = $this->postJson('/submit', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('cpt_slug');
    }

    public function test_access_request_rejected_for_unknown_cpt(): void
    {
        $data = $this->validAccessRequestData(['cpt_slug' => 'nonexistent']);

        $response = $this->postJson('/submit', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('cpt_slug');
    }
}
