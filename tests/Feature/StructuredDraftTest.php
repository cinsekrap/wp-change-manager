<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\CptType;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Content is written into the fields of the page type it becomes, rather than one
 * box of text. Which fields those are is already known: they are the ones a
 * change request to that page type uses.
 *
 * A kind of content with no page type of its own keeps the single box.
 */
class StructuredDraftTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function needsType(): CptType
    {
        return CptType::updateOrCreate(['slug' => 'needs'], [
            'name' => 'Needs',
            'request_mode' => 'normal',
            'content_kinds' => ['situation_support', 'appointment_prep'],
            'form_config' => ['content_areas' => [
                ['name' => 'Need Introduction', 'type' => 'richtext', 'reading_age' => true, 'word_limit' => 500],
                ['name' => 'Contact Details', 'type' => 'group', 'sub_fields' => [
                    ['name' => 'Phone number(s)', 'type' => 'text'],
                    ['name' => 'Contact notes', 'type' => 'textarea'],
                ]],
                ['name' => 'Questions and Answers', 'type' => 'group', 'repeatable' => true, 'sub_fields' => [
                    ['name' => 'Question', 'type' => 'text'],
                    ['name' => 'Answer', 'type' => 'textarea'],
                ]],
                ['name' => 'Documents', 'type' => 'file'],
            ]],
        ]);
    }

    private function content(string $contentType = 'situation_support'): ChangeRequest
    {
        $site = Site::firstOrCreate(['domain' => 'hcrg.test'], ['name' => 'HCRG', 'is_active' => true]);

        return ChangeRequest::create([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => 'content',
            'site_id' => $site->id,
            'page_url' => 'new-content',
            'page_title' => 'A working title',
            'cpt_slug' => 'content',
            'status' => 'in_progress',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'content_type' => $contentType,
        ]);
    }

    public function test_a_kind_with_a_page_type_is_written_into_its_fields(): void
    {
        $this->needsType();
        $request = $this->content('situation_support');

        $this->assertTrue($request->hasStructuredDraft());
        $this->assertSame('needs', $request->contentCptType()->slug);
        $this->assertCount(4, $request->contentFields());
    }

    public function test_a_kind_with_no_page_type_keeps_one_box(): void
    {
        $this->needsType();

        // Self help, Professional and Governance have no page type of their own.
        foreach (['referral_self', 'professional_prep', 'governance'] as $kind) {
            $request = $this->content($kind);
            $this->assertFalse($request->hasStructuredDraft(), "{$kind} should fall back to free text");
            $this->assertNull($request->contentCptType());
        }
    }

    public function test_a_page_type_with_no_fields_configured_keeps_one_box(): void
    {
        CptType::updateOrCreate(['slug' => 'needs'], [
            'name' => 'Needs', 'request_mode' => 'normal',
            'content_kinds' => ['situation_support'], 'form_config' => null,
        ]);

        $this->assertFalse($this->content('situation_support')->hasStructuredDraft());
    }

    public function test_the_editor_offers_every_field_including_the_repeating_ones(): void
    {
        $this->needsType();
        $request = $this->content();
        $this->loginAsAdmin();

        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertSee('Need Introduction')
            ->assertSee('Phone number(s)')
            ->assertSee('Questions and Answers')
            ->assertSee('fields[Questions and Answers][0][Question]', false)
            ->assertSee('Add another entry to Questions and Answers')
            // Attachments belong to the request, not to a copy field.
            ->assertSee('Attach files to the request rather than here.');
    }

    public function test_several_questions_and_answers_can_be_saved(): void
    {
        $this->needsType();
        $request = $this->content();
        $this->loginAsAdmin();

        $this->patch(route('admin.requests.draft', $request), ['fields' => [
            'Need Introduction' => 'What to do about back pain.',
            'Questions and Answers' => [
                ['Question' => 'Do I need a referral?', 'Answer' => 'No.'],
                ['Question' => 'How long is the wait?', 'Answer' => 'About four weeks.'],
            ],
        ]])->assertRedirect();

        $saved = $request->fresh()->draft_fields;
        $this->assertCount(2, $saved['Questions and Answers']);
        $this->assertSame('How long is the wait?', $saved['Questions and Answers'][1]['Question']);
    }

    public function test_the_fields_are_written_down_as_text_as_well(): void
    {
        $this->needsType();
        $request = $this->content();
        $this->loginAsAdmin();

        $this->patch(route('admin.requests.draft', $request), ['fields' => [
            'Need Introduction' => 'What to do about back pain.',
            'Contact Details' => ['Phone number(s)' => '0300 123', 'Contact notes' => 'Weekdays only.'],
            'Questions and Answers' => [['Question' => 'Do I need a referral?', 'Answer' => 'No.']],
        ]])->assertRedirect();

        // Clinical approval binds to a hash of draft_content and the reading-age
        // check reads it, so structured copy has to land there too.
        $text = $request->fresh()->draft_content;
        $this->assertStringContainsString('What to do about back pain.', $text);
        $this->assertStringContainsString('0300 123', $text);
        $this->assertStringContainsString('Do I need a referral?', $text);
        $this->assertStringNotContainsString('Documents', $text, 'An empty field should not be written down');
    }

    public function test_approved_structured_copy_is_locked_like_any_other(): void
    {
        $this->needsType();
        $request = $this->content();
        $this->loginAsAdmin();

        $this->patch(route('admin.requests.draft', $request), [
            'fields' => ['Need Introduction' => 'Version one.'],
        ]);
        $request->refresh();

        $request->approvers()->create([
            'name' => 'Dr Helen Johal', 'email' => 'h.johal@example.nhs.uk',
            'status' => 'approved', 'responded_at' => now(),
            'approved_content_hash' => $request->draftContentHash(),
            'approved_content_snapshot' => $request->draft_content,
        ]);
        $this->assertTrue($request->fresh()->hasBoundApproval());

        // The lock is enforced on the way in, not just hidden in the form.
        $this->patch(route('admin.requests.draft', $request), [
            'fields' => ['Need Introduction' => 'Version two.'],
        ])->assertSessionHasErrors('draft_content');

        $this->assertStringContainsString('Version one.', $request->fresh()->draft_content);
    }

    public function test_unlocking_lets_structured_copy_change_and_withdraws_the_approval(): void
    {
        $this->needsType();
        $request = $this->content();
        $this->loginAsAdmin();

        $this->patch(route('admin.requests.draft', $request), ['fields' => ['Need Introduction' => 'Version one.']]);
        $request->refresh();
        $request->approvers()->create([
            'name' => 'Dr Helen Johal', 'email' => 'h.johal@example.nhs.uk',
            'status' => 'approved', 'responded_at' => now(),
            'approved_content_hash' => $request->draftContentHash(),
            'approved_content_snapshot' => $request->draft_content,
        ]);

        $this->patch(route('admin.requests.draft', $request), [
            'fields' => ['Need Introduction' => 'Version two.'],
            'void_approval' => '1',
        ])->assertRedirect();

        $request->refresh();
        $this->assertStringContainsString('Version two.', $request->draft_content);
        $this->assertFalse($request->hasBoundApproval());
    }

    public function test_free_text_content_still_saves_the_old_way(): void
    {
        $this->needsType();
        $request = $this->content('governance');
        $this->loginAsAdmin();

        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertSee('name="draft_content"', false)
            ->assertDontSee('Questions and Answers');

        $this->patch(route('admin.requests.draft', $request), ['draft_content' => 'Plain copy.'])
            ->assertRedirect();

        $this->assertSame('Plain copy.', $request->fresh()->draft_content);
        $this->assertNull($request->fresh()->draft_fields);
    }

    public function test_the_approver_reads_it_in_sections(): void
    {
        $this->needsType();
        $request = $this->content();
        $this->loginAsAdmin();

        $this->patch(route('admin.requests.draft', $request), ['fields' => [
            'Need Introduction' => 'What to do about back pain.',
            'Questions and Answers' => [
                ['Question' => 'Do I need a referral?', 'Answer' => 'No, you can refer yourself.'],
                ['Question' => 'How long is the wait?', 'Answer' => 'About four weeks.'],
            ],
        ]]);

        $request->refresh();
        $request->updateQuietly(['status' => 'awaiting_approval']);
        $approver = $request->approvers()->create([
            'name' => 'Dr Helen Johal', 'email' => 'h.johal@example.nhs.uk',
            'status' => 'pending', 'token' => \App\Models\ChangeRequestApprover::generateToken(),
        ]);

        // A clinician reads labelled sections, not the flattened text the hash
        // happens to be taken over.
        $this->get(route('approval.show', $approver->token))
            ->assertSuccessful()
            ->assertSee('Need Introduction')
            ->assertSee('What to do about back pain.')
            ->assertSee('Do I need a referral?')
            ->assertSee('No, you can refer yourself.')
            ->assertSee('About four weeks.')
            // A field nobody filled in is not shown as an empty heading.
            ->assertDontSee('Contact Details');
    }

    public function test_free_text_copy_still_reaches_the_approver(): void
    {
        $this->needsType();
        $request = $this->content('governance');
        $request->updateQuietly(['status' => 'awaiting_approval', 'draft_content' => 'Plain copy for approval.']);

        $approver = $request->approvers()->create([
            'name' => 'Dr Helen Johal', 'email' => 'h.johal@example.nhs.uk',
            'status' => 'pending', 'token' => \App\Models\ChangeRequestApprover::generateToken(),
        ]);

        $this->get(route('approval.show', $approver->token))
            ->assertSuccessful()
            ->assertSee('Plain copy for approval.');
    }

    public function test_the_mapping_is_set_on_the_page_type_in_the_admin(): void
    {
        $this->needsType();
        $this->loginAsAdmin();

        // It used to live in a config file, so wiring up a page type somebody had
        // just added needed a code change and a deploy.
        $this->get(route('admin.cpts.edit', CptType::where('slug', 'needs')->first()))
            ->assertSuccessful()
            ->assertSee('Used for new content')
            ->assertSee('Explains a situation and what support exists')
            ->assertSee('name="content_kinds[]"', false);
    }

    public function test_claiming_a_kind_changes_where_content_is_written(): void
    {
        $this->needsType();
        $services = CptType::updateOrCreate(['slug' => 'services'], [
            'name' => 'Services', 'request_mode' => 'normal',
            'form_config' => ['content_areas' => [['name' => 'Service Title', 'type' => 'text']]],
        ]);

        $governance = $this->content('governance');
        $this->assertFalse($governance->hasStructuredDraft());

        $this->loginAsAdmin();
        $this->put(route('admin.cpts.update', $services), [
            'slug' => 'services', 'name' => 'Services', 'sort_order' => 0,
            'request_mode' => 'normal',
            'content_kinds' => ['governance'],
            'content_areas' => [['name' => 'Service Title', 'type' => 'text']],
        ])->assertRedirect();

        $this->assertTrue($governance->fresh()->hasStructuredDraft());
        $this->assertSame('services', $governance->fresh()->contentCptType()->slug);
    }

    public function test_a_kind_another_page_type_has_taken_cannot_be_claimed_twice(): void
    {
        $this->needsType();
        $services = CptType::updateOrCreate(['slug' => 'services'], [
            'name' => 'Services', 'request_mode' => 'normal',
            'form_config' => ['content_areas' => [['name' => 'Service Title', 'type' => 'text']]],
        ]);

        $this->loginAsAdmin();

        // Needs already claims situation_support; the form shows it as taken
        // rather than letting two page types fight over it.
        $this->get(route('admin.cpts.edit', $services))
            ->assertSuccessful()
            ->assertSee('Already used by Needs');
    }

    public function test_unticking_every_kind_returns_content_to_free_text(): void
    {
        $this->needsType();
        $request = $this->content('situation_support');
        $this->assertTrue($request->hasStructuredDraft());

        $this->loginAsAdmin();
        $this->put(route('admin.cpts.update', CptType::where('slug', 'needs')->first()), [
            'slug' => 'needs', 'name' => 'Needs', 'sort_order' => 0, 'request_mode' => 'normal',
            'content_areas' => [['name' => 'Need Introduction', 'type' => 'richtext']],
        ])->assertRedirect();

        // Sending no kinds means none, rather than leaving the old ones in place.
        $this->assertSame([], CptType::where('slug', 'needs')->first()->content_kinds);
        $this->assertFalse($request->fresh()->hasStructuredDraft());
    }

    public function test_sub_fields_keep_the_order_they_were_configured_in(): void
    {
        $this->needsType();
        $request = $this->content();
        $this->loginAsAdmin();

        // Deliberately sent back to front. A MySQL JSON column does not keep key
        // order, so reading them out of the stored value gives an order nobody
        // chose — and this text is what clinical approval is taken over.
        $this->patch(route('admin.requests.draft', $request), ['fields' => [
            'Contact Details' => [
                'Contact notes' => 'Weekdays only.',
                'Phone number(s)' => '0300 123',
            ],
        ]]);

        $text = $request->fresh()->draft_content;
        $this->assertLessThan(
            strpos($text, 'Contact notes'),
            strpos($text, 'Phone number(s)'),
            'Sub-fields are being written in the order the database returned them'
        );
    }

    public function test_the_approver_sees_sub_fields_in_that_same_order(): void
    {
        $this->needsType();
        $request = $this->content();
        $this->loginAsAdmin();

        $this->patch(route('admin.requests.draft', $request), ['fields' => [
            'Questions and Answers' => [['Answer' => 'No.', 'Question' => 'Do I need a referral?']],
        ]]);

        $request->refresh();
        $request->updateQuietly(['status' => 'awaiting_approval']);
        $approver = $request->approvers()->create([
            'name' => 'Dr Helen Johal', 'email' => 'h.johal@example.nhs.uk',
            'status' => 'pending', 'token' => \App\Models\ChangeRequestApprover::generateToken(),
        ]);

        $html = $this->get(route('approval.show', $approver->token))->assertSuccessful()->getContent();

        $this->assertLessThan(
            strpos($html, 'No.'),
            strpos($html, 'Do I need a referral?'),
            'The answer is being shown before the question'
        );
    }
}
