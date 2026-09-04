<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\Site;
use Tests\TestCase;

class ContentRequestSchemaTest extends TestCase
{
    private function site(string $name, string $domain): Site
    {
        return Site::create(['name' => $name, 'domain' => $domain, 'is_active' => true]);
    }

    private function contentRequest(Site $home, array $overrides = []): ChangeRequest
    {
        return ChangeRequest::create(array_merge([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => 'content',
            'site_id' => $home->id,
            'page_url' => 'new-content',
            'cpt_slug' => 'pages',
            'status' => 'suggested',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'content_type' => 'need',
            'content_brief' => [
                'achieve' => 'Stop people ringing to ask what happens first.',
                'audience' => ['patients', 'families'],
                'know_or_do' => 'Know what to bring.',
                'already_exists' => 'not_sure',
            ],
        ], $overrides));
    }

    public function test_a_content_request_round_trips_with_its_brief(): void
    {
        $home = $this->site('HCRG Care Group', 'hcrgcaregroup.com');
        $reference = $this->contentRequest($home)->reference;

        $found = ChangeRequest::where('reference', $reference)->firstOrFail();

        $this->assertTrue($found->isContentRequest());
        $this->assertFalse($found->isAccessRequest());
        $this->assertSame('need', $found->content_type);
        // The brief is cast to an array, not handed back as a JSON string.
        $this->assertIsArray($found->content_brief);
        $this->assertSame(['patients', 'families'], $found->content_brief['audience']);
    }

    public function test_additional_sites_carry_the_published_url_without_disturbing_the_main_home(): void
    {
        $home = $this->site('HCRG Care Group', 'hcrgcaregroup.com');
        $other = $this->site('Virgin Care Services', 'virgincare.co.uk');

        $request = $this->contentRequest($home);
        $request->additionalSites()->attach($other->id, [
            'published_url' => 'https://virgincare.co.uk/services/community-nursing',
            'published_title' => 'Community Nursing',
        ]);

        $request->refresh();

        // site_id stays the main home, so existing queries are untouched.
        $this->assertSame($home->id, $request->site_id);
        $this->assertCount(1, $request->additionalSites);
        $this->assertSame(
            'https://virgincare.co.uk/services/community-nursing',
            $request->additionalSites->first()->pivot->published_url
        );
    }

    public function test_a_site_cannot_be_attached_to_the_same_request_twice(): void
    {
        $home = $this->site('HCRG Care Group', 'hcrgcaregroup.com');
        $other = $this->site('Virgin Care Services', 'virgincare.co.uk');
        $request = $this->contentRequest($home);

        $request->additionalSites()->attach($other->id);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $request->additionalSites()->attach($other->id);
    }

    public function test_an_approval_records_the_copy_it_approved(): void
    {
        $home = $this->site('HCRG Care Group', 'hcrgcaregroup.com');
        $request = $this->contentRequest($home, ['status' => 'awaiting_approval']);

        $copy = 'The final approved wording.';
        $approver = ChangeRequestApprover::create([
            'change_request_id' => $request->id,
            'name' => 'Dr Approver',
            'email' => 'approver@example.com',
            'status' => 'approved',
            'responded_at' => now(),
            'approved_content_hash' => hash('sha256', $copy),
            'approved_content_snapshot' => $copy,
        ]);

        // The point of the binding: a later edit no longer matches what was signed off.
        $this->assertSame(hash('sha256', $copy), $approver->fresh()->approved_content_hash);
        $this->assertNotSame(
            $approver->fresh()->approved_content_hash,
            hash('sha256', $copy.' Plus an unapproved sentence.')
        );
    }

    public function test_status_options_differ_by_request_type(): void
    {
        $home = $this->site('HCRG Care Group', 'hcrgcaregroup.com');

        $content = $this->contentRequest($home);
        $change = $this->contentRequest($home, ['request_type' => 'change', 'status' => 'requested']);
        $access = $this->contentRequest($home, ['request_type' => 'access', 'status' => 'requested']);

        // Content gets the suggestion/funding states and can still be scheduled.
        $this->assertContains('awaiting_funding', $content->statusOptions());
        $this->assertContains('scheduled', $content->statusOptions());
        $this->assertNotContains('training', $content->statusOptions());

        // Change requests are unchanged by this work.
        $this->assertNotContains('awaiting_funding', $change->statusOptions());
        $this->assertNotContains('training', $change->statusOptions());
        $this->assertContains('scheduled', $change->statusOptions());

        // Access requests keep training and lose scheduling, as before.
        $this->assertContains('training', $access->statusOptions());
        $this->assertNotContains('scheduled', $access->statusOptions());
        $this->assertNotContains('awaiting_funding', $access->statusOptions());
    }

    public function test_content_statuses_do_not_pause_the_sla(): void
    {
        // Content being slow is reported truthfully rather than paused away.
        foreach (ChangeRequest::CONTENT_ONLY_STATUSES as $status) {
            $this->assertNotContains($status, ChangeRequest::SLA_PAUSED_STATUSES);
        }
    }
}
