<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\SitemapService;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    public function refresh(Site $site, SitemapService $sitemapService)
    {
        abort_unless($site->is_active, 404);

        $result = $sitemapService->refresh($site);
        return response()->json($result);
    }

    public function status(Site $site, SitemapService $sitemapService)
    {
        abort_unless($site->is_active, 404);

        return response()->json([
            'has_data' => $sitemapService->hasData($site),
            'needs_refresh' => $sitemapService->needsRefresh($site),
            'page_count' => $site->sitemapPages()->count(),
        ]);
    }

    public function pages(Request $request, Site $site)
    {
        abort_unless($site->is_active, 404);

        $cptSlug = $request->input('cpt_slug');
        $search = $request->input('search');

        $query = $site->sitemapPages();

        if ($cptSlug) {
            $query->where('cpt_slug', $cptSlug);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('page_title', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $pages = $query->orderBy('page_title')->get(['id', 'url', 'page_title', 'cpt_slug']);

        $cpts = $site->sitemapPages()
            ->select('cpt_slug')
            ->distinct()
            ->pluck('cpt_slug');

        return response()->json([
            'pages' => $pages,
            'cpts' => $cpts,
        ]);
    }
}
