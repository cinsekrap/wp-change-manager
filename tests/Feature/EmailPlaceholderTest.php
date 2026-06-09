<?php

namespace Tests\Feature;

use App\Mail\ApprovalOverridden;
use App\Mail\GroupApprovalSatisfied;
use App\Mail\RequestChase;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
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
}
