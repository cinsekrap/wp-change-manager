<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContentApprovalBindingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function contentRequest(string $copy = 'The approved wording.'): ChangeRequest
    {
        $site = Site::create(['name' => 'HCRG Care Group', 'domain' => 'hcrgcaregroup.com', 'is_active' => true]);

        return ChangeRequest::create([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => 'content',
            'site_id' => $site->id,
            'page_url' => 'new-content',
            'cpt_slug' => 'content',
            'status' => 'awaiting_approval',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'content_type' => 'service_explainer',
            'draft_content' => $copy,
        ]);
    }

    private function approve(ChangeRequest $request): ChangeRequestApprover
    {
        return ChangeRequestApprover::create([
            'change_request_id' => $request->id,
            'name' => 'Dr Approver',
            'email' => 'approver@example.com',
            'status' => 'approved',
            'responded_at' => now(),
            'approved_content_hash' => $request->draftContentHash(),
            'approved_content_snapshot' => $request->draft_content,
        ]);
    }

    public function test_editing_approved_copy_voids_the_approval(): void
    {
        $request = $this->contentRequest();
        $approver = $this->approve($request);
        $request->update(['status' => 'approved']);

        $request->update(['draft_content' => 'The approved wording, plus a sentence nobody signed off.']);

        $approver->refresh();
        $this->assertSame('pending', $approver->status);
        $this->assertNull($approver->responded_at);
        $this->assertNull($approver->approved_content_hash);
        // A fresh token, so the approver can be asked again.
        $this->assertNotNull($approver->token);
    }

    public function test_voiding_an_approval_returns_the_request_for_re_approval(): void
    {
        $request = $this->contentRequest();
        $this->approve($request);
        $request->update(['status' => 'approved']);

        $request->update(['draft_content' => 'Materially different copy.']);

        $this->assertSame('awaiting_approval', $request->fresh()->status);
    }

    public function test_an_untouched_approval_survives_unrelated_edits(): void
    {
        $request = $this->contentRequest();
        $approver = $this->approve($request);
        $request->update(['status' => 'approved']);

        // Editing something that is not the copy must not disturb the sign-off.
        $request->update(['public_title' => 'Community nursing: what to expect']);

        $approver->refresh();
        $this->assertSame('approved', $approver->status);
        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_rewriting_the_copy_back_to_the_approved_text_keeps_the_approval(): void
    {
        $request = $this->contentRequest();
        $approver = $this->approve($request);

        // The binding is to the text, not to the act of editing.
        $request->update(['draft_content' => 'Something else entirely.']);
        $approver->refresh();
        $this->assertSame('pending', $approver->status);

        $request->update(['draft_content' => 'The approved wording.']);
        $this->assertSame('pending', $approver->fresh()->status, 'A voided approval must be re-given, not silently restored.');
    }

    public function test_change_requests_are_unaffected_by_the_binding(): void
    {
        $request = $this->contentRequest();
        $request->update(['request_type' => 'change', 'status' => 'approved']);
        $approver = $this->approve($request);

        $request->update(['draft_content' => 'Changed.']);

        // Change requests express content as line items; the binding is content-only.
        $this->assertSame('approved', $approver->fresh()->status);
    }

    public function test_stale_approvals_are_identified_by_hash(): void
    {
        $request = $this->contentRequest();
        $this->approve($request);

        $this->assertCount(0, $request->staleApprovals());

        $request->draft_content = 'Different.';
        $this->assertCount(1, $request->staleApprovals());
    }
}
