<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestItem;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function validSubmissionData(array $overrides = []): array
    {
        $site = Site::create([
            'name' => 'Test Site',
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        return array_merge([
            'site_id' => $site->id,
            'page_url' => 'https://example.com/about',
            'page_title' => 'About Us',
            'cpt_slug' => 'page',
            'is_new_page' => false,
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'requester_phone' => '0123456789',
            'requester_role' => 'Content Editor',
            'check_answers' => [],
            'items' => [
                [
                    'action_type' => 'change',
                    'content_area' => 'Main Body',
                    'current_content' => 'We were founded in 1990.',
                    'description' => 'We were founded in 1992.',
                ],
            ],
        ], $overrides);
    }

    public function test_can_submit_change_request(): void
    {
        $data = $this->validSubmissionData();

        $response = $this->postJson('/submit', $data);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'reference', 'redirect']);

        $this->assertDatabaseCount('change_requests', 1);
        $this->assertDatabaseHas('change_requests', [
            'requester_email' => 'jane@example.com',
            'status' => 'requested',
        ]);
    }

    public function test_submission_requires_valid_site(): void
    {
        $data = $this->validSubmissionData(['site_id' => 99999]);

        $response = $this->postJson('/submit', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('site_id');
    }

    public function test_submission_creates_line_items(): void
    {
        $data = $this->validSubmissionData([
            'items' => [
                [
                    'action_type' => 'change',
                    'content_area' => 'Header',
                    'current_content' => 'Welcome to our site.',
                    'description' => 'Welcome to our new site.',
                ],
                [
                    'action_type' => 'add',
                    'content_area' => 'Sidebar',
                    'description' => 'Add a new sidebar widget.',
                ],
            ],
        ]);

        $response = $this->postJson('/submit', $data);

        $response->assertStatus(200);
        $this->assertDatabaseCount('change_request_items', 2);
        $this->assertDatabaseHas('change_request_items', ['content_area' => 'Header']);
        $this->assertDatabaseHas('change_request_items', ['content_area' => 'Sidebar']);
    }

    public function test_change_requires_current_content(): void
    {
        $data = $this->validSubmissionData([
            'items' => [
                ['action_type' => 'change', 'content_area' => 'Body', 'description' => 'Some new text.'],
            ],
        ]);

        $response = $this->postJson('/submit', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('items.0.current_content');
        $this->assertDatabaseCount('change_requests', 0);
    }

    public function test_change_rejects_noop_when_updated_equals_current(): void
    {
        $data = $this->validSubmissionData([
            'items' => [
                ['action_type' => 'change', 'content_area' => 'Body', 'current_content' => 'Same text.', 'description' => 'Same text.'],
            ],
        ]);

        $response = $this->postJson('/submit', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('items.0.description');
        $this->assertDatabaseCount('change_requests', 0);
    }

    public function test_change_persists_current_content(): void
    {
        $response = $this->postJson('/submit', $this->validSubmissionData());

        $response->assertStatus(200);
        $this->assertDatabaseHas('change_request_items', [
            'action_type' => 'change',
            'current_content' => 'We were founded in 1990.',
            'description' => 'We were founded in 1992.',
        ]);
    }

    public function test_submission_auto_adds_approvers_when_checks_pass(): void
    {
        $site = Site::create([
            'name' => 'Approval Site',
            'domain' => 'approval.com',
            'is_active' => true,
            'default_approvers' => [
                ['name' => 'Manager One', 'email' => 'mgr1@example.com'],
                ['name' => 'Manager Two', 'email' => 'mgr2@example.com'],
            ],
        ]);

        $data = [
            'site_id' => $site->id,
            'page_url' => 'https://approval.com/page',
            'cpt_slug' => 'page',
            'requester_name' => 'Submitter',
            'requester_email' => 'submitter@example.com',
            'check_answers' => [
                ['question' => 'Is this approved?', 'answer' => 'Yes', 'pass' => true],
                ['question' => 'Is content ready?', 'answer' => 'Yes', 'pass' => true],
            ],
            'items' => [
                ['action_type' => 'change', 'current_content' => 'Old text.', 'description' => 'Update text.'],
            ],
        ];

        $response = $this->postJson('/submit', $data);

        $response->assertStatus(200);

        $cr = ChangeRequest::first();
        $this->assertEquals('referred', $cr->status);
        $this->assertCount(2, $cr->approvers);
        $this->assertEquals('Manager One', $cr->approvers[0]->name);
    }

    public function test_submission_does_not_add_approvers_when_checks_fail(): void
    {
        $site = Site::create([
            'name' => 'Approval Site',
            'domain' => 'approval.com',
            'is_active' => true,
            'default_approvers' => [
                ['name' => 'Manager One', 'email' => 'mgr1@example.com'],
            ],
        ]);

        $data = [
            'site_id' => $site->id,
            'page_url' => 'https://approval.com/page',
            'cpt_slug' => 'page',
            'requester_name' => 'Submitter',
            'requester_email' => 'submitter@example.com',
            'check_answers' => [
                ['question' => 'Is this approved?', 'answer' => 'No', 'pass' => false],
            ],
            'items' => [
                ['action_type' => 'change', 'current_content' => 'Old text.', 'description' => 'Update text.'],
            ],
        ];

        $response = $this->postJson('/submit', $data);

        $response->assertStatus(200);

        $cr = ChangeRequest::first();
        $this->assertEquals('requested', $cr->status);
        $this->assertCount(0, $cr->approvers);
    }
}
