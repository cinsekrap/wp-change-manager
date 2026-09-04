<?php

namespace Tests\Feature;

use App\Mail\ApprovalRequested;
use App\Models\ChangeRequest;
use App\Models\EmailLog;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * What happens after a clinical approval is withdrawn: the copy changed, the
 * sign-off is gone, and it has to be sought again.
 */
class ContentReapprovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function approvedContentRequest(): ChangeRequest
    {
        $site = Site::create([
            'name' => 'HCRG Care Group',
            'domain' => 'hcrgcaregroup.com',
            'is_active' => true,
            'default_approvers' => [['name' => 'Dr Helen Johal', 'email' => 'h.johal@example.nhs.uk']],
        ]);

        $request = ChangeRequest::create([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => 'content',
            'site_id' => $site->id,
            'page_url' => 'new-content',
            'cpt_slug' => 'content',
            'status' => 'awaiting_approval',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'content_type' => 'service_explainer',
            'draft_content' => 'Version one.',
        ]);

        $request->approvers()->create([
            'name' => 'Dr Helen Johal',
            'email' => 'h.johal@example.nhs.uk',
            'status' => 'approved',
            'responded_at' => now(),
            'approved_content_hash' => $request->draftContentHash(),
            'approved_content_snapshot' => $request->draft_content,
            'token' => null,
        ]);

        $request->updateQuietly(['status' => 'approved']);

        return $request->fresh();
    }

    public function test_a_withdrawn_approval_is_askable_again(): void
    {
        $request = $this->approvedContentRequest();
        $approver = $request->approvers->first();

        $request->update(['draft_content' => 'Version two.']);
        $approver->refresh();

        $this->assertSame('pending', $approver->status);
        $this->assertNull($approver->responded_at);
        // A token has to be reissued or the approver has no way back in.
        $this->assertNotNull($approver->token);
        $this->assertFalse($request->fresh()->approvalsAllApproved());
    }

    public function test_re_sending_reaches_the_approver_whose_sign_off_was_withdrawn(): void
    {
        $request = $this->approvedContentRequest();
        $request->update(['draft_content' => 'Version two.']);

        $this->loginAsAdmin();
        $this->post(route('admin.requests.send-approval', $request))->assertRedirect();

        Mail::assertSent(ApprovalRequested::class, fn ($mail) => $mail->hasTo('h.johal@example.nhs.uk'));
    }

    public function test_re_sending_does_not_duplicate_the_approver(): void
    {
        $request = $this->approvedContentRequest();
        $request->update(['draft_content' => 'Version two.']);

        $this->loginAsAdmin();
        $this->post(route('admin.requests.send-approval', $request));

        // The site has a default approver of the same name; re-sending must reuse
        // the existing row rather than stacking up a second one.
        $this->assertCount(1, $request->fresh()->approvers);
    }

    public function test_approving_the_new_version_binds_to_the_new_copy(): void
    {
        $request = $this->approvedContentRequest();
        $request->update(['draft_content' => 'Version two.']);
        $approver = $request->approvers()->first();

        $this->post(route('approval.respond', $approver->token), [
            'status' => 'approved',
            'notes' => 'Happy with the revision.',
        ]);

        $approver->refresh();
        $request->refresh();

        $this->assertSame('approved', $approver->status);
        $this->assertSame($request->draftContentHash(), $approver->approved_content_hash);
        $this->assertSame('Version two.', $approver->approved_content_snapshot);
        $this->assertTrue($request->hasBoundApproval());
    }

    public function test_the_old_snapshot_does_not_survive_re_approval(): void
    {
        $request = $this->approvedContentRequest();
        $request->update(['draft_content' => 'Version two.']);
        $approver = $request->approvers()->first();

        $this->post(route('approval.respond', $approver->token), ['status' => 'approved']);

        // The audit answer to "what did they approve?" must be the current text.
        $this->assertStringNotContainsString('Version one.', (string) $approver->fresh()->approved_content_snapshot);
    }

    public function test_a_request_can_go_round_the_loop_more_than_once(): void
    {
        $request = $this->approvedContentRequest();

        foreach (['Version two.', 'Version three.'] as $copy) {
            $request->update(['draft_content' => $copy]);
            $this->assertSame('awaiting_approval', $request->fresh()->status);

            $approver = $request->approvers()->first();
            $this->post(route('approval.respond', $approver->token), ['status' => 'approved']);

            $request->refresh();
            $this->assertTrue($request->hasBoundApproval(), "Failed to re-approve after: {$copy}");
        }

        // Still one approver, one live sign-off, bound to the latest text.
        $this->assertCount(1, $request->approvers);
        $this->assertSame('Version three.', $request->approvers->first()->approved_content_snapshot);
    }

    public function test_re_sending_leaves_a_content_request_awaiting_clinical_approval(): void
    {
        $request = $this->approvedContentRequest();
        $request->update(['draft_content' => 'Version two.']);
        $this->assertSame('awaiting_approval', $request->fresh()->status);

        $this->loginAsAdmin();
        $this->post(route('admin.requests.send-approval', $request));

        // Re-sending should leave a content request awaiting clinical approval,
        // not push it into the change lane's referral status.
        $this->assertSame('awaiting_approval', $request->fresh()->status);
    }
}
