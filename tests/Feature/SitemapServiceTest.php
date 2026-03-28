<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Services\SitemapService;
use Tests\TestCase;

class SitemapServiceTest extends TestCase
{
    public function test_domain_normalisation(): void
    {
        $site = Site::create([
            'name' => 'Test Site',
            'domain' => 'https://example.com/',
            'is_active' => true,
        ]);

        $this->assertEquals('example.com', $site->domain);
    }

    public function test_needs_refresh_returns_true_for_empty_site(): void
    {
        $site = Site::create([
            'name' => 'Empty Site',
            'domain' => 'empty.com',
            'is_active' => true,
        ]);

        $service = new SitemapService();

        $this->assertTrue($service->needsRefresh($site));
    }
}
