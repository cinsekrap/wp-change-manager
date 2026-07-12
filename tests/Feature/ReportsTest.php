<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestStatusLog;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function createRequest(array $overrides = [], ?string $doneDaysAfter = null): ChangeRequest
    {
        static $counter = 0;
        $counter++;

        $site = Site::firstOrCreate(
            ['domain' => 'example.com'],
            ['name' => 'Test Site', 'is_active' => true],
        );

        $cr = ChangeRequest::create(array_merge([
            'reference' => sprintf('WCR-REPORT-%03d', $counter),
            'site_id' => $site->id,
            'page_url' => 'https://example.com/page',
            'cpt_slug' => 'page',
            'status' => 'requested',
            'requester_name' => 'Reporter',
            'requester_email' => 'reporter@example.com',
        ], $overrides));

        if ($doneDaysAfter !== null) {
            $cr->update(['status' => 'done']);
            $log = ChangeRequestStatusLog::create([
                'change_request_id' => $cr->id,
                'user_id' => null,
                'old_status' => 'requested',
                'new_status' => 'done',
            ]);
            ChangeRequestStatusLog::whereKey($log->id)
                ->update(['created_at' => $cr->created_at->copy()->addDays((int) $doneDaysAfter)]);
        }

        return $cr;
    }

    public function test_reports_page_renders_with_kpis(): void
    {
        $this->loginAsAdmin();
        $this->createRequest();
        $this->createRequest([], doneDaysAfter: '2');

        $this->get(route('admin.reports'))
            ->assertOk()
            ->assertSee('Reports')
            ->assertSee('Submitted vs Completed')
            ->assertSee('Average Days to Complete')
            ->assertSee('Requests by Site')
            ->assertSee('Approvals');
    }

    public function test_date_filter_excludes_requests_outside_range(): void
    {
        $this->loginAsAdmin();
        $inRange = $this->createRequest();
        $outOfRange = $this->createRequest();
        ChangeRequest::whereKey($outOfRange->id)->update(['created_at' => now()->subYears(2)]);

        $response = $this->get(route('admin.reports', [
            'from' => now()->subMonth()->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
        ]));

        $response->assertOk();
        // KPI "Submitted" should count only the in-range request
        $this->assertStringContainsString('Submitted', $response->getContent());
        $this->assertMatchesRegularExpression(
            '/Submitted<\/div>\s*<div[^>]*>1</',
            $response->getContent()
        );
    }

    public function test_invalid_date_range_is_rejected(): void
    {
        $this->loginAsAdmin();

        $this->get(route('admin.reports', ['from' => '2026-06-01', 'to' => '2026-01-01']))
            ->assertSessionHasErrors('to');
    }
}
