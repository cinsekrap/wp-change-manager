<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\SitemapService;
use Illuminate\Console\Command;

class RefreshSitemap extends Command
{
    protected $signature = 'sitemap:refresh {site_id? : The ID of the site to refresh (omit for all)}';
    protected $description = 'Refresh sitemap cache for one or all sites';

    public function handle(SitemapService $sitemapService): int
    {
        $siteId = $this->argument('site_id');

        if ($siteId) {
            $site = Site::find($siteId);
            if (!$site) {
                $this->error("Site #{$siteId} not found.");
                return 1;
            }
            return $this->refreshSite($site, $sitemapService);
        }

        $sites = Site::active()->whereNotNull('sitemap_url')->get();

        if ($sites->isEmpty()) {
            $this->info('No active sites with sitemap URLs found.');
            return 0;
        }

        $failed = 0;
        foreach ($sites as $site) {
            if ($this->refreshSite($site, $sitemapService) !== 0) {
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Done. Refreshed {$sites->count()} site(s)" . ($failed ? ", {$failed} failed." : '.'));

        return $failed > 0 ? 1 : 0;
    }

    protected function refreshSite(Site $site, SitemapService $sitemapService): int
    {
        $this->info("Refreshing: {$site->name} ({$site->domain})...");

        $result = $sitemapService->refresh($site);

        if ($result['success']) {
            $this->info("  {$result['message']}");
            return 0;
        }

        $this->error("  {$result['message']}");
        return 1;
    }
}
