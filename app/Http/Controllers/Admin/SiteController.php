<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSiteRequest;
use App\Http\Requests\Admin\UpdateSiteRequest;
use App\Models\Site;
use App\Services\SitemapService;

class SiteController extends Controller
{
    public function index()
    {
        $sites = Site::withCount('sitemapPages')
            ->orderBy('name')
            ->paginate(25);

        return view('admin.sites.index', compact('sites'));
    }

    public function create()
    {
        return view('admin.sites.form', ['site' => new Site()]);
    }

    public function store(StoreSiteRequest $request)
    {
        Site::create($request->validated());

        return redirect()->route('admin.sites.index')->with('success', 'Site created.');
    }

    public function edit(Site $site)
    {
        return view('admin.sites.form', compact('site'));
    }

    public function update(UpdateSiteRequest $request, Site $site)
    {
        $site->update($request->validated());

        return redirect()->route('admin.sites.index')->with('success', 'Site updated.');
    }

    public function destroy(Site $site)
    {
        if ($site->changeRequests()->exists()) {
            return back()->with('error', 'Cannot delete a site with existing change requests. Deactivate it instead.');
        }

        $site->delete();
        return redirect()->route('admin.sites.index')->with('success', 'Site deleted.');
    }

    public function refreshSitemap(Site $site, SitemapService $sitemapService)
    {
        $result = $sitemapService->refresh($site);

        if (request()->wantsJson()) {
            return response()->json($result);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
