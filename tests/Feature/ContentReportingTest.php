<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicSite\WizardController;
use App\Models\ChangeRequest;
use App\Models\Site;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContentReportingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::forget('content_wait_days');
    }

    private function site(): Site
    {
        return Site::firstOrCreate(
            ['domain' => 'hcrgcaregroup.com'],
            ['name' => 'HCRG Care Group', 'is_active' => true]
        );
    }

    private function completed(string $type, int $days): ChangeRequest
    {
        $request = ChangeRequest::create([
            'reference' => 'CR-'.strtoupper(bin2hex(random_bytes(4))),
            'request_type' => $type,
            'site_id' => $this->site()->id,
            'page_url' => $type === 'content' ? 'new-content' : '/a-page',
            'cpt_slug' => $type === 'content' ? 'content' : 'pages',
            'status' => 'done',
            'requester_name' => 'Jane Doe',
            'requester_email' => 'jane@example.com',
        ]);

        // Completed $days after it was raised.
        $request->forceFill([
            'created_at' => now()->subDays($days),
            'updated_at' => now(),
        ])->saveQuietly();

        return $request;
    }

    public function test_the_wizard_shows_a_wait_read_from_completed_content_requests(): void
    {
        foreach ([40, 50, 60] as $days) {
            $this->completed('content', $days);
        }

        // ~50 days, from the requests themselves rather than a hand-kept number.
        $this->assertSame(50, WizardController::averageContentWaitDays());

        $this->get('/')->assertSuccessful()->assertSee('The wait for new content is currently around');
    }

    public function test_fast_change_requests_do_not_drag_the_content_figure_down(): void
    {
        foreach ([40, 50, 60] as $days) {
            $this->completed('content', $days);
        }
        foreach ([1, 1, 2] as $days) {
            $this->completed('change', $days);
        }

        // The lanes are reported separately, so changes cannot flatter content.
        $this->assertSame(50, WizardController::averageContentWaitDays());
    }

    public function test_no_figure_is_shown_until_there_is_enough_history(): void
    {
        $this->completed('content', 30);
        $this->completed('content', 40);

        // Two completions is not an average worth publishing.
        $this->assertNull(WizardController::averageContentWaitDays());
        $this->get('/')->assertSuccessful()->assertDontSee('The wait for new content is currently around');
    }

    public function test_the_cached_wait_is_a_scalar(): void
    {
        foreach ([10, 20, 30] as $days) {
            $this->completed('content', $days);
        }

        WizardController::averageContentWaitDays();

        // Never cache objects: a cached Carbon has taken the dashboard down before.
        $this->assertIsNotObject(Cache::get('content_wait_days'));
        $this->assertIsNumeric(Cache::get('content_wait_days'));
    }

    public function test_reports_split_the_average_by_lane(): void
    {
        $this->completed('content', 60);
        $this->completed('change', 2);

        $this->loginAsAdmin();

        $this->get(route('admin.reports'))
            ->assertSuccessful()
            ->assertSee('Changes:')
            ->assertSee('Content:');
    }
}
