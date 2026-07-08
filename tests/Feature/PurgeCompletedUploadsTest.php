<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\ChangeRequestItem;
use App\Models\ChangeRequestItemFile;
use App\Models\ChangeRequestStatusLog;
use App\Models\Site;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeCompletedUploadsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function createRequestWithFile(string $status, ?int $closedDaysAgo = null): ChangeRequestItemFile
    {
        static $counter = 0;
        $counter++;

        $site = Site::firstOrCreate(
            ['domain' => 'example.com'],
            ['name' => 'Test Site', 'is_active' => true],
        );

        $request = ChangeRequest::create([
            'reference' => sprintf('WCR-20260601-%03d', $counter),
            'site_id' => $site->id,
            'page_url' => 'https://example.com/test',
            'cpt_slug' => 'page',
            'status' => $status,
            'requester_name' => 'John Doe',
            'requester_email' => 'john@example.com',
        ]);

        if ($closedDaysAgo !== null) {
            $log = ChangeRequestStatusLog::create([
                'change_request_id' => $request->id,
                'user_id' => null,
                'old_status' => 'requested',
                'new_status' => $status,
            ]);
            ChangeRequestStatusLog::whereKey($log->id)
                ->update(['created_at' => now()->subDays($closedDaysAgo)]);
        }

        $item = ChangeRequestItem::create([
            'change_request_id' => $request->id,
            'action_type' => 'edit',
            'description' => 'Test item',
            'sort_order' => 0,
        ]);

        $storedPath = "uploads/{$request->reference}/test-file.pdf";
        Storage::disk('local')->put($storedPath, 'dummy content');

        return ChangeRequestItemFile::create([
            'change_request_item_id' => $item->id,
            'original_filename' => 'brief.pdf',
            'stored_path' => $storedPath,
            'mime_type' => 'application/pdf',
            'file_size' => 13,
        ]);
    }

    public function test_purges_files_for_requests_closed_over_30_days_ago(): void
    {
        $file = $this->createRequestWithFile('done', closedDaysAgo: 31);

        $this->artisan('uploads:purge-completed')->assertSuccessful();

        $file->refresh();
        $this->assertNotNull($file->purged_at);
        Storage::disk('local')->assertMissing($file->stored_path);
        $this->assertFalse(Storage::disk('local')->exists(dirname($file->stored_path)));
    }

    public function test_purges_declined_and_cancelled_requests(): void
    {
        $declined = $this->createRequestWithFile('declined', closedDaysAgo: 40);
        $cancelled = $this->createRequestWithFile('cancelled', closedDaysAgo: 40);

        $this->artisan('uploads:purge-completed')->assertSuccessful();

        $this->assertNotNull($declined->refresh()->purged_at);
        $this->assertNotNull($cancelled->refresh()->purged_at);
    }

    public function test_leaves_recently_closed_requests_alone(): void
    {
        $file = $this->createRequestWithFile('done', closedDaysAgo: 5);

        $this->artisan('uploads:purge-completed')->assertSuccessful();

        $this->assertNull($file->refresh()->purged_at);
        Storage::disk('local')->assertExists($file->stored_path);
    }

    public function test_leaves_open_requests_alone(): void
    {
        $file = $this->createRequestWithFile('approved', closedDaysAgo: 60);

        $this->artisan('uploads:purge-completed')->assertSuccessful();

        $this->assertNull($file->refresh()->purged_at);
        Storage::disk('local')->assertExists($file->stored_path);
    }

    public function test_reopened_then_reclosed_request_gets_fresh_grace_period(): void
    {
        $file = $this->createRequestWithFile('done', closedDaysAgo: 45);

        // Reopened and closed again recently — latest status change wins
        $request = $file->item->changeRequest;
        ChangeRequestStatusLog::create([
            'change_request_id' => $request->id,
            'user_id' => null,
            'old_status' => 'approved',
            'new_status' => 'done',
        ]);

        $this->artisan('uploads:purge-completed')->assertSuccessful();

        $this->assertNull($file->refresh()->purged_at);
    }

    public function test_purged_file_download_is_gone_and_listed_as_removed(): void
    {
        $this->loginAsAdmin();
        $file = $this->createRequestWithFile('done', closedDaysAgo: 31);
        $this->artisan('uploads:purge-completed')->assertSuccessful();

        $request = $file->item->changeRequest;

        $this->get(route('admin.requests.download', [$request, $file]))->assertStatus(410);

        $this->get(route('admin.requests.show', $request))
            ->assertOk()
            ->assertSee('brief.pdf')
            ->assertSee('after the request was closed');
    }
}
