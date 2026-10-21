<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\CptType;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The create form used to save its wording as the draft. It is a summary or old
 * content to work from, so the form now keeps it with the brief — and what it
 * saved before moves there too, unless it may have become real copy since.
 */
class ExistingWordingMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function content(array $overrides = []): ChangeRequest
    {
        return ChangeRequest::create(array_merge([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => 'content',
            'site_id' => null,
            'page_url' => 'new-content',
            'page_title' => 'A working title',
            'cpt_slug' => 'content',
            'is_new_page' => true,
            'status' => 'suggested',
            'requester_name' => null,
            'requester_email' => null,
            'content_type' => 'appointment_prep',
            'content_brief' => ['achieve' => 'A purpose.'],
            'draft_content' => 'Pasted from the old leaflet.',
        ], $overrides));
    }

    private function migrate(): void
    {
        (require database_path('migrations/2026_10_21_000001_move_create_form_wording_into_the_brief.php'))->up();
    }

    public function test_wording_the_form_saved_moves_into_the_brief(): void
    {
        $request = $this->content();

        $this->migrate();
        $request->refresh();

        $this->assertNull($request->draft_content);
        $this->assertSame('Pasted from the old leaflet.', $request->content_brief['existing_wording']);
        $this->assertSame('A purpose.', $request->content_brief['achieve']);
    }

    public function test_wording_the_fields_kept_hidden_moves_even_while_being_written(): void
    {
        CptType::updateOrCreate(['slug' => 'needs'], [
            'name' => 'Needs',
            'request_mode' => 'normal',
            'content_kinds' => ['appointment_prep'],
            'form_config' => ['content_areas' => [['name' => 'Need Introduction', 'type' => 'richtext']]],
        ]);
        $request = $this->content(['status' => 'in_progress']);

        $this->migrate();

        $this->assertSame('Pasted from the old leaflet.', $request->fresh()->content_brief['existing_wording']);
    }

    public function test_copy_that_may_be_real_by_now_stays_the_draft(): void
    {
        // Being written in a single box: the designer may have rewritten it there.
        $writing = $this->content(['status' => 'in_progress']);
        // Already written into the page type's fields.
        $placed = $this->content(['draft_fields' => ['Need Introduction' => 'Real copy.']]);
        // A public suggestion: its draft was written by the team, not pasted in.
        $suggested = $this->content(['requester_name' => 'Jane Doe', 'requester_email' => 'jane@example.com']);
        // Sent for clinical approval: the draft is what the clinician is reading.
        $sent = $this->content();
        $sent->approvers()->create([
            'name' => 'Dr Helen Johal', 'email' => 'h.johal@example.nhs.uk',
            'status' => 'pending', 'token' => ChangeRequestApprover::generateToken(),
        ]);

        $this->migrate();

        foreach (compact('writing', 'placed', 'suggested', 'sent') as $name => $request) {
            $request->refresh();
            $this->assertSame('Pasted from the old leaflet.', $request->draft_content, "The {$name} draft was moved");
            $this->assertArrayNotHasKey('existing_wording', $request->content_brief, "The {$name} brief gained wording");
        }
    }
}
