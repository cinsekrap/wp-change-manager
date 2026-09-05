<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\CptType;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContentRequestSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function sites(): array
    {
        $home = Site::create(['name' => 'HCRG Care Group', 'domain' => 'hcrgcaregroup.com', 'is_active' => true]);
        $other = Site::create(['name' => 'Virgin Care Services', 'domain' => 'virgincare.co.uk', 'is_active' => true]);
        CptType::create(['slug' => 'pages', 'name' => 'Pages', 'request_mode' => 'normal']);

        return [$home, $other];
    }

    private function payload(Site $home, array $overrides = []): array
    {
        return array_merge([
            'site_id' => $home->id,
            'page_url' => 'new-content',
            'page_title' => null,
            'cpt_slug' => 'content',
            'is_new_page' => true,
            'request_type' => 'content',
            'content_type' => 'situation_support',
            'content_brief' => [
                'achieve' => 'Stop people ringing to ask what happens at the first appointment.',
                'audience' => ['patients', 'families'],
                'know_or_do' => 'Know what to bring and how to change the appointment.',
                'measure' => 'Fewer calls to the single point of access.',
                'already_exists' => 'not_sure',
                'already_exists_detail' => null,
            ],
            'additional_site_ids' => [],
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'priority' => 'normal',
            'check_answers' => [],
            // The wizard sends this key with an empty array rather than omitting
            // it, and a present field is what rules fire on. Tests that left it
            // out passed while every real submission was rejected.
            'items' => [],
        ], $overrides);
    }

    public function test_a_content_request_is_accepted_without_line_items(): void
    {
        [$home] = $this->sites();

        $this->postJson('/submit', $this->payload($home))->assertSuccessful();

        $request = ChangeRequest::where('request_type', 'content')->firstOrFail();
        $this->assertSame('situation_support', $request->content_type);
        $this->assertSame(['patients', 'families'], $request->content_brief['audience']);
        $this->assertCount(0, $request->items);
    }

    public function test_a_content_request_starts_as_a_suggestion(): void
    {
        [$home] = $this->sites();

        $this->postJson('/submit', $this->payload($home))->assertSuccessful();

        // Nothing is committed until it is agreed and the hours are funded.
        $this->assertSame('suggested', ChangeRequest::where('request_type', 'content')->first()->status);
    }

    public function test_additional_sites_are_attached_and_the_home_is_left_alone(): void
    {
        [$home, $other] = $this->sites();

        $this->postJson('/submit', $this->payload($home, ['additional_site_ids' => [$other->id]]))
            ->assertSuccessful();

        $request = ChangeRequest::where('request_type', 'content')->firstOrFail();
        $this->assertSame($home->id, $request->site_id);
        $this->assertSame([$other->id], $request->additionalSites->pluck('id')->all());
    }

    public function test_the_brief_is_required(): void
    {
        [$home] = $this->sites();

        $payload = $this->payload($home);
        unset($payload['content_brief']['achieve']);

        $this->postJson('/submit', $payload)->assertStatus(422)
            ->assertJsonValidationErrors('content_brief.achieve');
    }

    public function test_an_unknown_content_type_is_rejected(): void
    {
        [$home] = $this->sites();

        $this->postJson('/submit', $this->payload($home, ['content_type' => 'not_a_real_type']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('content_type');
    }

    public function test_change_requests_still_require_line_items(): void
    {
        [$home] = $this->sites();

        // The guard that content relaxes must not leak into the change lane.
        $this->postJson('/submit', [
            'site_id' => $home->id,
            'page_url' => '/contact-us',
            'cpt_slug' => 'pages',
            'request_type' => 'change',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
        ])->assertStatus(422)->assertJsonValidationErrors('items');
    }

    public function test_the_wizard_renders_the_content_type_options(): void
    {
        $this->sites();

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('What are you working on?')
            ->assertSee('Explains a service and how it works')
            ->assertSee('Governance')
            // The checkbox the content lane replaces is gone.
            ->assertDontSee('id="isNewPage"', false);
    }

    public function test_a_content_request_is_accepted_with_an_empty_items_array(): void
    {
        [$home] = $this->sites();

        // Not the same as omitting the key: items: [] is a present field, so any
        // rule that fires on presence applies to it.
        $this->postJson('/submit', $this->payload($home, ['items' => []]))
            ->assertSuccessful();

        $this->assertSame(1, ChangeRequest::where('request_type', 'content')->count());
    }

    public function test_a_change_request_is_still_refused_without_line_items(): void
    {
        [$home] = $this->sites();

        foreach ([[], null] as $items) {
            $this->postJson('/submit', $this->payload($home, [
                'request_type' => 'change',
                'page_url' => '/a-page',
                'cpt_slug' => 'pages',
                'content_type' => null,
                'content_brief' => null,
                'items' => $items,
            ]))->assertStatus(422)->assertJsonValidationErrors('items');
        }
    }

    public function test_the_wizard_still_sends_items_for_content(): void
    {
        $js = file_get_contents(resource_path('views/public/partials/wizard/wizard-scripts.blade.php'));

        // If the wizard ever stops sending the key, the empty-array case above
        // stops being the one that matters and this needs revisiting.
        $this->assertStringContainsString('items: items.map(item => ({', $js);
    }
}
