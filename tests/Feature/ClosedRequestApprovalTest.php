<?php

namespace Tests\Feature;

use App\Mail\ApprovalRequested;
use App\Models\ChangeRequest;
use App\Models\ClinicalApprover;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * A closed request is not waiting on anyone. Adding an approver emailed them
 * straight away with no regard for the request's status, so a named clinician
 * could be asked to approve something that had already been declined — and the
 * link in that email told them exactly that when they followed it.
 */
class ClosedRequestApprovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function request(string $status, string $type = 'content'): ChangeRequest
    {
        $site = Site::firstOrCreate(['domain' => 'hcrg.test'], [
            'name' => 'HCRG',
            'is_active' => true,
            'default_approvers' => [['name' => 'Site Approver', 'email' => 'site@example.com']],
        ]);

        return ChangeRequest::create([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => $type,
            'site_id' => $site->id,
            'page_url' => $type === 'content' ? 'new-content' : '/a-page',
            'page_title' => 'A page',
            'cpt_slug' => $type === 'content' ? 'content' : 'pages',
            'status' => $status,
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'content_type' => 'service_explainer',
            'draft_content' => 'Some copy.',
            'rejection_reason' => in_array($status, ['declined', 'cancelled']) ? 'Not going ahead.' : null,
        ]);
    }

    private function clinician(): ClinicalApprover
    {
        return ClinicalApprover::create([
            'name' => 'Dr Helen Johal',
            'email' => 'h.johal@example.nhs.uk',
            'job_title' => 'Clinical Lead',
            'is_active' => true,
        ]);
    }

    public function test_nobody_is_asked_to_approve_a_closed_request(): void
    {
        $clinician = $this->clinician();
        $this->loginAsAdmin();

        foreach (ChangeRequest::TERMINAL_STATUSES as $status) {
            $request = $this->request($status);

            $this->post(route('admin.requests.approvers.add', $request), [
                'clinical_approver_id' => $clinician->id,
            ])->assertSessionHas('error');

            $this->assertCount(0, $request->fresh()->approvers, "An approver was added to a {$status} request");
        }

        // The email is the real harm: a clinician asked for a decision already made.
        Mail::assertNothingSent();
    }

    public function test_sending_a_closed_request_for_approval_is_refused(): void
    {
        $this->loginAsAdmin();

        foreach (ChangeRequest::TERMINAL_STATUSES as $status) {
            $request = $this->request($status, 'change');

            $this->post(route('admin.requests.send-approval', $request))
                ->assertSessionHas('error');

            $this->assertSame($status, $request->fresh()->status);
        }

        // A change request would otherwise pull in the site's default approvers.
        Mail::assertNothingSent();
    }

    public function test_the_refusal_names_the_status_and_says_what_to_do(): void
    {
        $request = $this->request('declined');
        $this->loginAsAdmin();

        $this->post(route('admin.requests.approvers.add', $request), [
            'clinical_approver_id' => $this->clinician()->id,
        ])->assertSessionHas('error', fn ($error) => str_contains($error, 'Declined')
            && str_contains($error, 'Change its status'));
    }

    public function test_the_form_is_not_offered_on_a_closed_request(): void
    {
        $request = $this->request('declined');
        $this->clinician();
        $this->loginAsAdmin();

        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertDontSee('Ask a clinical approver')
            ->assertSee('nobody can be asked to approve it');
    }

    public function test_an_open_request_is_unaffected(): void
    {
        $request = $this->request('in_progress');
        $clinician = $this->clinician();
        $this->loginAsAdmin();

        $this->post(route('admin.requests.approvers.add', $request), [
            'clinical_approver_id' => $clinician->id,
        ])->assertRedirect();

        $this->assertCount(1, $request->fresh()->approvers);
        Mail::assertSent(ApprovalRequested::class);
    }

    public function test_a_blocked_status_says_what_it_is_waiting_for(): void
    {
        $request = $this->request('awaiting_approval');
        $request->approvers()->create([
            'name' => 'Dr Helen Johal',
            'email' => 'h.johal@example.nhs.uk',
            'status' => 'pending',
            'token' => \App\Models\ChangeRequestApprover::generateToken(),
        ]);

        $this->loginAsAdmin();

        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            // "Approved (approvals required)" read like a contradiction.
            ->assertDontSee('approvals required')
            ->assertSee('Approved — waiting on 1 approver', false);
    }

    public function test_the_count_is_the_number_actually_outstanding(): void
    {
        $request = $this->request('awaiting_approval');
        foreach (['a@example.nhs.uk', 'b@example.nhs.uk'] as $email) {
            $request->approvers()->create([
                'name' => 'Dr '.$email,
                'email' => $email,
                'status' => 'pending',
                'token' => \App\Models\ChangeRequestApprover::generateToken(),
            ]);
        }
        $request->approvers()->create([
            'name' => 'Dr Already Said Yes',
            'email' => 'c@example.nhs.uk',
            'status' => 'approved',
            'responded_at' => now(),
        ]);

        $this->loginAsAdmin();

        // Three approvers, two still to respond.
        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertSee('waiting on 2 approvers', false);
    }

    public function test_nothing_is_appended_when_the_move_is_allowed(): void
    {
        $request = $this->request('awaiting_approval');
        $request->approvers()->create([
            'name' => 'Dr Helen Johal',
            'email' => 'h.johal@example.nhs.uk',
            'status' => 'approved',
            'responded_at' => now(),
        ]);

        $this->loginAsAdmin();

        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertDontSee('waiting on')
            ->assertDontSee('approvals not complete');
    }
}
