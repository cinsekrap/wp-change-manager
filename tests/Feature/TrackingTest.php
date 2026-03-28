<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicSite\TrackingController;
use App\Models\ChangeRequest;
use App\Models\Site;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    private function createChangeRequest(): ChangeRequest
    {
        $site = Site::create([
            'name' => 'Test Site',
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        return ChangeRequest::create([
            'reference' => 'WCR-20260327-001',
            'site_id' => $site->id,
            'page_url' => 'https://example.com/test',
            'cpt_slug' => 'page',
            'status' => 'requested',
            'requester_name' => 'John Doe',
            'requester_email' => 'john@example.com',
        ]);
    }

    public function test_signed_tracking_url_works(): void
    {
        $cr = $this->createChangeRequest();

        $signedUrl = TrackingController::signedUrl($cr);

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
    }

    public function test_invalid_signature_redirects(): void
    {
        $cr = $this->createChangeRequest();

        // Tamper with the URL by adding garbage to the signature
        $response = $this->get("/track/{$cr->reference}?signature=invalid");

        $response->assertRedirect(route('tracking'));
    }

    public function test_manual_lookup_works(): void
    {
        $cr = $this->createChangeRequest();

        $response = $this->post('/track', [
            'reference' => $cr->reference,
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200);
    }
}
