<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSiteRequest;
use App\Http\Requests\Admin\UpdateSiteRequest;
use App\Models\Site;
use App\Models\User;
use App\Services\AuditService;
use App\Services\SitemapService;

class SiteController extends Controller
{
    public function index()
    {
        $sites = Site::with('defaultAssignee')
            ->withCount('sitemapPages')
            ->orderBy('name')
            ->paginate(25);

        return view('admin.sites.index', compact('sites'));
    }

    public function create()
    {
        $adminUsers = User::where('is_active', true)
            ->whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_EDITOR])
            ->orderBy('name')
            ->get();

        return view('admin.sites.form', ['site' => new Site(), 'adminUsers' => $adminUsers]);
    }

    public function store(StoreSiteRequest $request)
    {
        $site = Site::create($request->validated());

        AuditService::log(
            action: 'created',
            model: $site,
            description: "Created site: {$site->name}",
            newValues: $request->validated(),
        );

        return redirect()->route('admin.sites.index')->with('success', 'Site created.');
    }

    public function edit(Site $site)
    {
        $adminUsers = User::where('is_active', true)
            ->whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_EDITOR])
            ->orderBy('name')
            ->get();

        return view('admin.sites.form', compact('site', 'adminUsers'));
    }

    public function update(UpdateSiteRequest $request, Site $site)
    {
        $oldValues = $site->only(['name', 'domain', 'sitemap_url', 'is_active']);
        $site->update($request->validated());
        $newValues = $site->only(['name', 'domain', 'sitemap_url', 'is_active']);

        AuditService::log(
            action: 'updated',
            model: $site,
            description: "Updated site: {$site->name}",
            oldValues: $oldValues,
            newValues: $newValues,
        );

        return redirect()->route('admin.sites.index')->with('success', 'Site updated.');
    }

    public function destroy(Site $site)
    {
        if ($site->changeRequests()->exists()) {
            return back()->with('error', 'Cannot delete a site with existing change requests. Deactivate it instead.');
        }

        $siteName = $site->name;
        $site->delete();

        AuditService::log(
            action: 'deleted',
            model: $site,
            description: "Deleted site: {$siteName}",
            oldValues: ['name' => $siteName],
        );

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
