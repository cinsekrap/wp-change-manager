<?php

namespace Tests\Feature;

use App\Mail\ApprovalOverridden;
use App\Mail\ClarificationRequested;
use App\Mail\ClarificationResponded;
use App\Mail\GroupApprovalSatisfied;
use App\Mail\RequestChase;
use App\Mail\RequestOnHold;
use App\Mail\TrainingConfirmed;
use App\Mail\TrainingRequested;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\CptType;
use App\Models\Site;
use App\Models\User;
use Tests\TestCase;

class EmailPlaceholderTest extends TestCase
{
    private function changeRequest(array $overrides = []): ChangeRequest
    {
        $site = Site::create(['name' => 'Surrey Child and Family', 'domain' => 'scf.example', 'is_active' => true]);

        return ChangeRequest::create(array_merge([
            'reference' => 'WCR-20260608-011',
            'site_id' => $site->id,
            'page_url' => 'https://scf.example/becoming-a-parent',
            'page_title' => 'Becoming A Parent For The First Time',
            'cpt_slug' => 'page',
            'status' => 'referred',
            'requester_name' => 'Req Uester',
            'requester_email' => 'req@example.com',
        ], $overrides));
    }

    /** The default body uses {overridden_by}; with no custom override it must still be substituted. */
    public function test_approval_overridden_default_body_substitutes_name(): void
    {
        $overrider = User::factory()->create(['name' => 'Nic Chambers-Parkes']);
        $cr = $this->changeRequest([
            'approval_overridden' => true,
            'approval_overridden_by' => $overrider->id,
            'approval_overridden_at' => now(),
        ]);
        $approver = ChangeRequestApprover::create([
            'change_request_id' => $cr->id,
            'name' => 'Pauline Bigwood',
            'email' => 'pauline@example.com',
            'status' => 'pending',
        ]);

        $html = (new ApprovalOverridden($cr, $approver))->render();

        $this->assertStringNotContainsString('{overridden_by}', $html);
        $this->assertStringContainsString('Nic Chambers-Parkes has progressed this change request', $html);
    }

    /** group_approval_satisfied default body uses {satisfied_by}. */
    public function test_group_approval_satisfied_default_body_substitutes_name(): void
    {
        $cr = $this->changeRequest();
        $approver = ChangeRequestApprover::create([
            'change_request_id' => $cr->id,
            'name' => 'Group Member',
            'email' => 'gm@example.com',
            'group' => 'Clinical',
            'status' => 'pending',
        ]);

        $html = (new GroupApprovalSatisfied($cr, $approver, 'Dr Smith'))->render();

        $this->assertStringNotContainsString('{satisfied_by}', $html);
        $this->assertStringContainsString('Dr Smith has approved this request', $html);
    }

    /** request_chase default body uses {stale_hours}. */
    public function test_request_chase_default_body_substitutes_hours(): void
    {
        $cr = $this->changeRequest();
        // updated_at drives stale_hours; make it clearly in the past.
        ChangeRequest::where('id', $cr->id)->update(['updated_at' => now()->subHours(50)]);

        $html = (new RequestChase($cr->fresh()))->render();

        $this->assertStringNotContainsString('{stale_hours}', $html);
        // Substituted to a whole number of hours (no Carbon float leaking through).
        $this->assertStringContainsString('has been inactive for 50 hours', $html);
    }

    /** request_on_hold shows the hold reason and the fixed SLA-pause note. */
    public function test_request_on_hold_renders_reason_and_sla_note(): void
    {
        $cr = $this->changeRequest([
            'status' => 'on_hold',
            'hold_reason' => 'Waiting for sign-off from the service lead.',
        ]);

        $html = (new RequestOnHold($cr))->render();

        $this->assertStringNotContainsString('{reference}', $html);
        $this->assertStringNotContainsString('{hold_reason}', $html);
        $this->assertStringContainsString('Waiting for sign-off from the service lead.', $html);
        $this->assertStringContainsString('are paused', $html);
        $this->assertStringContainsString('WCR-20260608-011', $html);
    }

    /** clarification_requested shows the question and links the respond page. */
    public function test_clarification_requested_renders_question_and_respond_link(): void
    {
        $cr = $this->changeRequest([
            'status' => 'awaiting_user',
            'clarification_message' => 'Which phone number is correct?',
            'clarification_requested_at' => now(),
        ]);

        $html = (new ClarificationRequested($cr))->render();

        $this->assertStringNotContainsString('{reference}', $html);
        $this->assertStringNotContainsString('{clarification_message}', $html);
        $this->assertStringContainsString('Which phone number is correct?', $html);
        $this->assertStringContainsString('/respond/' . $cr->reference, $html);
        $this->assertStringContainsString('are on hold', $html);
    }

    /** clarification_response default body uses {requester_name}. */
    public function test_clarification_response_default_body_substitutes_requester(): void
    {
        $cr = $this->changeRequest();

        $html = (new ClarificationResponded($cr, 'The second number is correct.', 1))->render();

        $this->assertStringNotContainsString('{requester_name}', $html);
        $this->assertStringContainsString('Req Uester has responded', $html);
        $this->assertStringContainsString('The second number is correct.', $html);
    }

    private function accessRequest(): ChangeRequest
    {
        CptType::create([
            'slug' => 'events',
            'name' => 'Events',
            'request_mode' => 'self_service',
            'training_url' => 'https://example.com/training-video',
        ]);

        return $this->changeRequest([
            'request_type' => 'access',
            'cpt_slug' => 'events',
            'status' => 'training',
            'access_recipient_name' => 'Alex Recipient',
            'access_recipient_email' => 'alex@example.com',
            'training_token' => 'token-abc',
            'training_sent_at' => now(),
        ]);
    }

    /** training_requested email links both the video and the confirmation page. */
    public function test_training_requested_renders_video_and_confirm_links(): void
    {
        $html = (new TrainingRequested($this->accessRequest()))->render();

        $this->assertStringContainsString('https://example.com/training-video', $html);
        $this->assertStringContainsString('/training/token-abc', $html);
        $this->assertStringContainsString('Alex Recipient', $html);
    }

    /** training_confirmed default body uses {recipient_name}. */
    public function test_training_confirmed_default_body_substitutes_recipient(): void
    {
        $cr = $this->accessRequest();
        $cr->update(['status' => 'trained', 'training_confirmed_at' => now()]);

        $html = (new TrainingConfirmed($cr->fresh()))->render();

        $this->assertStringNotContainsString('{recipient_name}', $html);
        $this->assertStringContainsString('Alex Recipient has confirmed', $html);
    }
}
