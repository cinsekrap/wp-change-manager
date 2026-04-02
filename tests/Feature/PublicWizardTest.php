<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\Site;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicWizardTest extends TestCase
{
    public function test_wizard_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_confirmation_page_loads(): void
    {
        $site = Site::create([
            'name' => 'Test Site',
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        $changeRequest = ChangeRequest::create([
            'reference' => 'WCR-20260327-001',
            'site_id' => $site->id,
            'page_url' => 'https://example.com/test',
            'cpt_slug' => 'page',
            'status' => 'requested',
            'requester_name' => 'John Doe',
            'requester_email' => 'john@example.com',
        ]);

        $url = URL::signedRoute('confirmation', ['reference' => $changeRequest->reference]);

        $response = $this->get($url);

        $response->assertStatus(200);
    }

    public function test_tracking_page_loads(): void
    {
        $response = $this->get('/track');

        $response->assertStatus(200);
    }

    public function test_tracking_lookup_works(): void
    {
        $site = Site::create([
            'name' => 'Test Site',
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        $changeRequest = ChangeRequest::create([
            'reference' => 'WCR-20260327-001',
            'site_id' => $site->id,
            'page_url' => 'https://example.com/test',
            'cpt_slug' => 'page',
            'status' => 'requested',
            'requester_name' => 'John Doe',
            'requester_email' => 'john@example.com',
        ]);

        $response = $this->post('/track', [
            'reference' => $changeRequest->reference,
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200);
    }

    public function test_tracking_lookup_fails_with_wrong_email(): void
    {
        $site = Site::create([
            'name' => 'Test Site',
            'domain' => 'example.com',
            'is_active' => true,
        ]);

        ChangeRequest::create([
            'reference' => 'WCR-20260327-001',
            'site_id' => $site->id,
            'page_url' => 'https://example.com/test',
            'cpt_slug' => 'page',
            'status' => 'requested',
            'requester_name' => 'John Doe',
            'requester_email' => 'john@example.com',
        ]);

        $response = $this->post('/track', [
            'reference' => 'WCR-20260327-001',
            'email' => 'wrong@example.com',
        ]);

        $response->assertRedirect('/track');
        $response->assertSessionHas('error');
    }
}
