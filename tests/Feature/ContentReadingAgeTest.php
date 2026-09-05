<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\Site;
use App\Support\ReadingAge;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Copy written inside the tool is held to the same reading-age standard as copy
 * somebody asks for through the wizard. The check lived only in the public
 * wizard, so content written by the team — the copy that actually gets
 * published — was never measured at all.
 */
class ContentReadingAgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private string $simple = 'If you are coming to see us, you do not need to bring much. '
        . 'Bring a list of any medicines you take. Bring your glasses if you wear them. '
        . 'A friend or family member can come in with you if you would like that.';

    private string $complex = 'Prior to attendance at the aforementioned appointment, service users are '
        . 'requested to ensure the provision of a comprehensive pharmacological inventory. '
        . 'Additionally, the utilisation of corrective optical apparatus should be facilitated '
        . 'where such apparatus is habitually employed by the individual concerned.';

    private function contentRequest(array $overrides = []): ChangeRequest
    {
        $site = Site::create(['name' => 'HCRG', 'domain' => 'hcrg.test', 'is_active' => true]);

        return ChangeRequest::create(array_merge([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(3))),
            'request_type' => 'content',
            'site_id' => $site->id,
            'page_url' => 'new-content',
            'page_title' => 'Appointment explainer',
            'cpt_slug' => 'content',
            'status' => 'in_progress',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
            'content_type' => 'appointment_prep',
        ], $overrides));
    }

    public function test_the_fixtures_actually_differ_in_reading_age(): void
    {
        // Everything below is worthless if these two score the same.
        $this->assertLessThanOrEqual(13, ReadingAge::grade($this->simple));
        $this->assertGreaterThan(13, ReadingAge::grade($this->complex));
    }

    public function test_the_create_form_measures_the_copy_as_it_is_written(): void
    {
        $this->loginAsAdmin();

        $this->get(route('admin.requests.content.create'))
            ->assertSuccessful()
            ->assertSee('data-reading-age-badge-for="draft_content"', false)
            ->assertSee('data-reading-age-warning-for="draft_content"', false)
            // The gate has to be passable — this is guidance, not a hard block.
            ->assertSee('Save anyway', false);
    }

    public function test_the_draft_editor_measures_the_copy_the_designer_imports(): void
    {
        $request = $this->contentRequest(['draft_content' => $this->complex]);

        $this->loginAsAdmin();

        $this->get(route('admin.requests.show', $request))
            ->assertSuccessful()
            ->assertSee('data-reading-age-badge-for="draftContent"', false)
            ->assertSee('data-reading-age-warning-for="draftContent"', false);
    }

    public function test_the_approver_is_shown_the_reading_age_of_the_copy(): void
    {
        $request = $this->contentRequest(['status' => 'awaiting_approval', 'draft_content' => $this->complex]);
        $approver = ChangeRequestApprover::create([
            'change_request_id' => $request->id,
            'name' => 'Dr Helen Johal',
            'email' => 'h.johal@example.nhs.uk',
            'token' => ChangeRequestApprover::generateToken(),
            'status' => 'pending',
        ]);

        $age = ReadingAge::grade($this->complex);

        $this->get(route('approval.show', $approver->token))
            ->assertSuccessful()
            ->assertSee("Reading age: {$age}", false)
            // The last person to read this before it publishes should be told.
            ->assertSee('reads older than most of our audience', false);
    }

    public function test_simple_copy_is_not_flagged_to_the_approver(): void
    {
        $request = $this->contentRequest(['status' => 'awaiting_approval', 'draft_content' => $this->simple]);
        $approver = ChangeRequestApprover::create([
            'change_request_id' => $request->id,
            'name' => 'Dr Helen Johal',
            'email' => 'h.johal@example.nhs.uk',
            'token' => ChangeRequestApprover::generateToken(),
            'status' => 'pending',
        ]);

        $this->get(route('approval.show', $approver->token))
            ->assertSuccessful()
            ->assertSee('Reading age: '.ReadingAge::grade($this->simple), false)
            ->assertDontSee('reads older than most of our audience', false);
    }

    public function test_copy_too_short_to_score_shows_no_reading_age(): void
    {
        $request = $this->contentRequest(['status' => 'awaiting_approval', 'draft_content' => 'Too short to score.']);
        $approver = ChangeRequestApprover::create([
            'change_request_id' => $request->id,
            'name' => 'Dr Helen Johal',
            'email' => 'h.johal@example.nhs.uk',
            'token' => ChangeRequestApprover::generateToken(),
            'status' => 'pending',
        ]);

        // A number invented from two sentences would be worse than no number.
        $this->get(route('approval.show', $approver->token))
            ->assertSuccessful()
            ->assertDontSee('Reading age:', false);
    }

    public function test_the_partial_is_included_once_however_many_fields_use_it(): void
    {
        $request = $this->contentRequest(['draft_content' => $this->simple]);

        $this->loginAsAdmin();

        $html = $this->get(route('admin.requests.show', $request))->assertSuccessful()->getContent();

        // Two copies of the script means two submit handlers on the same form.
        $this->assertSame(1, substr_count($html, 'function calculateReadingAge'));
    }

    public function test_the_javascript_matches_the_php_it_was_ported_from(): void
    {
        $js = file_get_contents(resource_path('views/partials/reading-age.blade.php'));

        // These two implementations have to agree or the badge says one thing
        // while the approval page says another.
        $this->assertStringContainsString('0.39 * (words.length / sentenceCount) + 11.8 * (totalSyllables / words.length) - 15.59', $js);
        $this->assertStringContainsString('const MIN_WORDS = '.ReadingAge::MIN_WORDS.';', $js);
        $this->assertStringContainsString('const MIN_WORDS_COMPARISON = '.ReadingAge::MIN_WORDS_FOR_COMPARISON.';', $js);
    }
}
