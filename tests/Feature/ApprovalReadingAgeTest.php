<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestItem;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApprovalReadingAgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private string $simple = 'If your child is not going to come to school you must let us know. '
        . 'Call the office before nine in the morning. Tell us why they are not coming. '
        . 'If they are ill, say what is wrong so we can help you and keep them safe.';

    private string $complex = 'In the event that your child will fail to attend their education setting, '
        . 'you are required to notify the establishment accordingly. Please telephone the '
        . 'administrative office prior to the commencement of the school day. Communicate the '
        . 'rationale for the non-attendance so appropriate safeguarding measures may be undertaken.';

    private function approvalPageFor(string $current, string $updated): \Illuminate\Testing\TestResponse
    {
        $site = Site::create(['name' => 'Test Site', 'domain' => 'example.com', 'is_active' => true]);
        $cr = ChangeRequest::create([
            'reference' => 'WCR-20260609-001',
            'site_id' => $site->id,
            'page_url' => 'https://example.com/attendance',
            'page_title' => 'Attendance',
            'cpt_slug' => 'page',
            'status' => 'referred',
            'requester_name' => 'Req',
            'requester_email' => 'req@example.com',
        ]);
        ChangeRequestItem::create([
            'change_request_id' => $cr->id,
            'action_type' => 'change',
            'content_area' => 'Body',
            'current_content' => $current,
            'description' => $updated,
            'sort_order' => 0,
        ]);
        $approver = ChangeRequestApprover::create([
            'change_request_id' => $cr->id,
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'token' => ChangeRequestApprover::generateToken(),
            'status' => 'pending',
        ]);

        return $this->get("/approve/{$approver->token}");
    }

    public function test_flags_reading_age_increase_and_shows_diff(): void
    {
        $response = $this->approvalPageFor($this->simple, $this->complex);

        $response->assertOk();
        // Guidance banner + per-item flag
        $response->assertSee('make content harder for our audience', false);
        $response->assertSee('This change raises the reading age', false);
        // Inline diff is rendered (added wording wrapped in <ins>)
        $response->assertSee('<ins', false);
        $response->assertSee('establishment', false);
        // Approve goes through a confirmation step
        $response->assertSee('id="approveConfirmPanel"', false);
        $response->assertSee('Yes, approve anyway', false);
    }

    public function test_no_flag_when_change_simplifies(): void
    {
        // Reverse direction: complex -> simple lowers the reading age.
        $response = $this->approvalPageFor($this->complex, $this->simple);

        $response->assertOk();
        $response->assertDontSee('This change raises the reading age', false);
        $response->assertDontSee('make content harder for our audience', false);
        // No approve confirmation step for a normal change.
        $response->assertDontSee('id="approveConfirmPanel"', false);
        // Diff still renders.
        $response->assertSee('<del', false);
    }
}
