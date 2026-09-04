<?php

namespace Tests\Feature;

use App\Mail\ApprovalRequested;
use App\Models\ChangeRequest;
use App\Models\ClinicalApprover;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Content is not approved for a site — one sign-off covers every site it goes to —
 * so its approvers come from a managed list of named clinicians rather than from
 * whichever site happens to be its main home, or from free text.
 */
class ClinicalApproverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function siteWithApprovers(): Site
    {
        return Site::create([
            'name' => 'HCRG Care Group',
            'domain' => 'hcrgcaregroup.com',
            'is_active' => true,
            'default_approvers' => [['name' => 'Site Approver', 'email' => 'site@example.com']],
        ]);
    }

    private function contentRequest(Site $site, string $status = 'in_progress'): ChangeRequest
    {
        return ChangeRequest::create([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => 'content',
            'site_id' => $site->id,
            'page_url' => 'new-content',
            'cpt_slug' => 'content',
            'status' => $status,
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'content_type' => 'service_explainer',
            'draft_content' => 'Copy awaiting review.',
        ]);
    }

    private function clinician(array $overrides = []): ClinicalApprover
    {
        return ClinicalApprover::create(array_merge([
            'name' => 'Dr Helen Johal',
            'email' => 'h.johal@example.nhs.uk',
            'job_title' => 'Clinical Lead, Community Nursing',
            'areas_of_expertise' => 'Continence, wound care, long-term conditions in older adults',
            'is_active' => true,
        ], $overrides));
    }

    public function test_a_content_request_never_inherits_the_sites_approvers(): void
    {
        $request = $this->contentRequest($this->siteWithApprovers());

        $this->loginAsAdmin();
        $this->post(route('admin.requests.send-approval', $request));

        // The site has a default approver; content must not borrow it.
        $this->assertCount(0, $request->fresh()->approvers);
        Mail::assertNothingSent();
    }

    public function test_sending_content_for_approval_without_a_clinician_is_refused(): void
    {
        $request = $this->contentRequest($this->siteWithApprovers());

        $this->loginAsAdmin();
        $this->post(route('admin.requests.send-approval', $request))->assertSessionHas('error');

        $this->assertSame('in_progress', $request->fresh()->status);
    }

    public function test_a_change_request_still_inherits_the_sites_approvers(): void
    {
        $site = $this->siteWithApprovers();
        $request = $this->contentRequest($site);
        $request->updateQuietly(['request_type' => 'change', 'status' => 'requested', 'page_url' => '/a-page']);

        $this->loginAsAdmin();
        $this->post(route('admin.requests.send-approval', $request->fresh()));

        $this->assertCount(1, $request->fresh()->approvers);
        Mail::assertSent(ApprovalRequested::class);
    }

    public function test_a_content_approver_must_come_from_the_managed_list(): void
    {
        $request = $this->contentRequest($this->siteWithApprovers());

        $this->loginAsAdmin();

        // Free text is fine for a wording change; it is not a clinical record.
        $this->post(route('admin.requests.approvers.add', $request), [
            'name' => 'Somebody I Just Typed',
            'email' => 'anyone@example.com',
        ])->assertSessionHasErrors('clinical_approver_id');

        $this->assertCount(0, $request->fresh()->approvers);
    }

    public function test_an_inactive_clinician_cannot_be_asked(): void
    {
        $request = $this->contentRequest($this->siteWithApprovers());
        $retired = $this->clinician(['is_active' => false]);

        $this->loginAsAdmin();
        $this->post(route('admin.requests.approvers.add', $request), [
            'clinical_approver_id' => $retired->id,
        ])->assertSessionHasErrors('clinical_approver_id');
    }

    public function test_asking_a_clinician_records_their_name_and_role_and_emails_them(): void
    {
        $request = $this->contentRequest($this->siteWithApprovers());
        $clinician = $this->clinician();

        $this->loginAsAdmin();
        $this->post(route('admin.requests.approvers.add', $request), [
            'clinical_approver_id' => $clinician->id,
        ])->assertRedirect();

        $approver = $request->fresh()->approvers->first();

        // The record has to say who signed off, not just a bare name.
        $this->assertSame('Dr Helen Johal — Clinical Lead, Community Nursing', $approver->name);
        $this->assertSame('h.johal@example.nhs.uk', $approver->email);
        Mail::assertSent(ApprovalRequested::class, fn ($mail) => $mail->hasTo('h.johal@example.nhs.uk'));
    }

    public function test_the_same_clinician_is_not_asked_twice(): void
    {
        $request = $this->contentRequest($this->siteWithApprovers());
        $clinician = $this->clinician();

        $this->loginAsAdmin();
        $this->post(route('admin.requests.approvers.add', $request), ['clinical_approver_id' => $clinician->id]);
        $this->post(route('admin.requests.approvers.add', $request), ['clinical_approver_id' => $clinician->id]);

        $this->assertCount(1, $request->fresh()->approvers);
    }

    public function test_the_picker_shows_expertise_so_the_right_person_can_be_chosen(): void
    {
        $request = $this->contentRequest($this->siteWithApprovers());
        $this->clinician();

        $this->loginAsAdmin();

        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertSee('Dr Helen Johal — Clinical Lead, Community Nursing')
            ->assertSee('Continence, wound care, long-term conditions in older adults');
    }

    public function test_the_admin_can_manage_the_list(): void
    {
        $this->loginAsAdmin();

        $this->post(route('admin.clinical-approvers.store'), [
            'name' => 'Dr New Starter',
            'email' => 'new@example.nhs.uk',
            'job_title' => 'Consultant',
            'areas_of_expertise' => 'Sexual health',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('clinical_approvers', ['email' => 'new@example.nhs.uk', 'is_active' => true]);
    }

    public function test_removing_a_clinician_deactivates_rather_than_deletes_them(): void
    {
        $clinician = $this->clinician();

        $this->loginAsAdmin();
        $this->delete(route('admin.clinical-approvers.destroy', $clinician))->assertRedirect();

        // Sign-offs already given name this person; the record must not lose them.
        $this->assertDatabaseHas('clinical_approvers', ['id' => $clinician->id, 'is_active' => false]);
    }
}
