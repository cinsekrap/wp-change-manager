<?php

namespace App\Services;

use App\Models\CptType;
use App\Models\Site;
use App\Models\SitemapPage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SitemapService
{
    protected int $timeout = 30;

    public function refresh(Site $site): array
    {
        try {
            $sitemapUrl = $site->sitemap_url ?: $this->discover($site->domain);

            if (!$sitemapUrl) {
                return ['success' => false, 'message' => 'Could not find a sitemap for this site.'];
            }

            // Persist the discovered URL so we don't probe every time
            if (!$site->sitemap_url) {
                $site->update(['sitemap_url' => $sitemapUrl]);
            }

            $urls = $this->fetchUrls($sitemapUrl);

            if (empty($urls)) {
                return ['success' => false, 'message' => 'No URLs found in sitemap.'];
            }

            $cptSlugs = CptType::active()->pluck('slug')->toArray();

            $count = 0;
            foreach ($urls as $url) {
                $this->upsertPage($site, $url, $cptSlugs);
                $count++;
            }

            // Remove pages no longer in the sitemap
            $site->sitemapPages()
                ->whereNotIn('url', $urls)
                ->delete();

            return ['success' => true, 'message' => "Refreshed {$count} pages.", 'count' => $count];
        } catch (\Exception $e) {
            Log::error("Sitemap refresh failed for site {$site->id}: {$e->getMessage()}");
            return ['success' => false, 'message' => 'Failed to update site data. Please try again or contact the marketing team.'];
        }
    }

    protected function discover(string $domain): ?string
    {
        $candidates = [
            "https://{$domain}/sitemap_index.xml",
            "https://{$domain}/sitemap.xml",
        ];

        foreach ($candidates as $url) {
            try {
                $response = Http::timeout(10)->get($url);
                if ($response->successful() && @simplexml_load_string($response->body()) !== false) {
                    return $url;
                }
            } catch (\Exception $e) {
                // Try next candidate
            }
        }

        return null;
    }

    protected function fetchUrls(string $sitemapUrl): array
    {
        $response = Http::timeout($this->timeout)->get($sitemapUrl);

        if (!$response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()} fetching {$sitemapUrl}");
        }

        $xml = @simplexml_load_string($response->body());

        if ($xml === false) {
            throw new \RuntimeException("Failed to parse XML from {$sitemapUrl}");
        }

        // Check if this is a sitemap index
        if (isset($xml->sitemap)) {
            return $this->fetchFromIndex($xml);
        }

        // Regular sitemap — extract URLs
        return $this->extractUrls($xml);
    }

    protected function fetchFromIndex(\SimpleXMLElement $xml): array
    {
        $urls = [];

        foreach ($xml->sitemap as $sitemap) {
            $childUrl = (string) $sitemap->loc;

            if (empty($childUrl)) {
                continue;
            }

            try {
                $response = Http::timeout($this->timeout)->get($childUrl);

                if (!$response->successful()) {
                    Log::warning("Failed to fetch child sitemap: {$childUrl}");
                    continue;
                }

                $childXml = @simplexml_load_string($response->body());

                if ($childXml === false) {
                    Log::warning("Failed to parse child sitemap XML: {$childUrl}");
                    continue;
                }

                $urls = array_merge($urls, $this->extractUrls($childXml));
            } catch (\Exception $e) {
                Log::warning("Error fetching child sitemap {$childUrl}: {$e->getMessage()}");
            }
        }

        return $urls;
    }

    protected function extractUrls(\SimpleXMLElement $xml): array
    {
        $urls = [];

        // Handle namespaced XML (common in sitemaps)
        $namespaces = $xml->getNamespaces(true);

        if (isset($namespaces[''])) {
            $xml->registerXPathNamespace('sm', $namespaces['']);
            $entries = $xml->xpath('//sm:url/sm:loc');
        } else {
            $entries = $xml->xpath('//url/loc');
        }

        if ($entries) {
            foreach ($entries as $loc) {
                $url = trim((string) $loc);
                if (!empty($url)) {
                    $urls[] = $url;
                }
            }
        }

        return array_unique($urls);
    }

    protected function upsertPage(Site $site, string $url, array $cptSlugs): void
    {
        $cptSlug = $this->detectCpt($url, $cptSlugs);
        $pageTitle = $this->deriveTitle($url);

        SitemapPage::updateOrCreate(
            ['site_id' => $site->id, 'url' => $url],
            ['cpt_slug' => $cptSlug, 'page_title' => $pageTitle]
        );
    }

    protected function detectCpt(string $url, array $cptSlugs): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!$path || $path === '/') {
            return 'page';
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        if (empty($segments)) {
            return 'page';
        }

        $firstSegment = strtolower($segments[0]);

        return in_array($firstSegment, $cptSlugs) ? $firstSegment : 'page';
    }

    protected function deriveTitle(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!$path || $path === '/') {
            return 'Home';
        }

        $segments = array_filter(explode('/', trim($path, '/')));
        $lastSegment = end($segments);

        if (!$lastSegment) {
            return 'Home';
        }

        return Str::title(str_replace(['-', '_'], ' ', $lastSegment));
    }

    public function needsRefresh(Site $site): bool
    {
        $oldest = $site->sitemapPages()->min('updated_at');

        if (!$oldest) {
            return true;
        }

        return now()->diffInHours($oldest) >= 24;
    }

    public function hasData(Site $site): bool
    {
        return $site->sitemapPages()->exists();
    }
}
