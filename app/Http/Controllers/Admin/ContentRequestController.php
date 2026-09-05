<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Content the team starts itself.
 *
 * Everything else in this tool begins with somebody asking for it. A content
 * designer working from a strategy, a gap analysis or a content audit has no
 * requester to point at and may not yet know which site the page belongs to —
 * "content we want to exist" is a legitimate state. It still enters at
 * `suggested` and goes through the same gates as a suggestion from the wizard,
 * because the point of those gates is that nothing is committed until it is
 * agreed and funded, whoever raised it.
 *
 * Every field here is optional bar the working title: the designer can fill in
 * as much of the brief as they already know and leave the rest.
 */
class ContentRequestController extends Controller
{
    public function create()
    {
        return view('admin.requests.create-content', [
            'sites' => Site::where('is_active', true)->orderBy('name')->get(),
            'adminUsers' => User::admins()->orderBy('name')->get(),
            'contentTypes' => config('content-types'),
            'audiences' => config('content-audiences'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'page_title' => 'required|string|max:255',
            'content_type' => ['nullable', Rule::in(array_keys(config('content-types')))],
            'site_id' => 'nullable|exists:sites,id',
            'additional_site_ids' => 'nullable|array',
            'additional_site_ids.*' => 'exists:sites,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => ['nullable', Rule::in(ChangeRequest::PRIORITIES)],
            'estimated_hours' => 'nullable|numeric|min:0|max:9999',
            'deadline_date' => 'nullable|date',
            'deadline_reason' => 'nullable|string|max:1000',
            'draft_content' => 'nullable|string',
            'brief' => 'nullable|array',
            'brief.achieve' => 'nullable|string|max:5000',
            'brief.know_or_do' => 'nullable|string|max:5000',
            'brief.audience' => 'nullable|array',
            'brief.audience.*' => ['string', Rule::in(array_keys(config('content-audiences')))],
            'brief.measure' => 'nullable|string|max:1000',
            'brief.already_exists' => 'nullable|in:yes,no,not_sure',
            'brief.already_exists_detail' => 'nullable|string|max:2000',
        ]);

        // The main home cannot also be an additional site, and an additional site
        // list of one that repeats the home is just noise.
        $additional = collect($validated['additional_site_ids'] ?? [])
            ->reject(fn ($id) => (int) $id === (int) ($validated['site_id'] ?? 0))
            ->values();

        $brief = collect($validated['brief'] ?? [])
            ->filter(fn ($value) => filled($value))
            ->all();

        $changeRequest = DB::transaction(function () use ($validated, $additional, $brief) {
            $changeRequest = ChangeRequest::create([
                'reference' => ChangeRequest::generateReference(),
                'request_type' => 'content',
                'site_id' => $validated['site_id'] ?? null,
                // Content has no page until it is published; the wizard uses the
                // same placeholder so page history skips the whole lane.
                'page_url' => 'new-content',
                'page_title' => $validated['page_title'],
                'cpt_slug' => 'content',
                'is_new_page' => true,
                'content_type' => $validated['content_type'] ?? null,
                'content_brief' => $brief ?: null,
                'estimated_hours' => $validated['estimated_hours'] ?? null,
                'draft_content' => $validated['draft_content'] ?? null,
                // Enters at the same gate as a suggestion from the public wizard.
                'status' => 'suggested',
                'priority' => $validated['priority'] ?? 'normal',
                'assigned_to' => $validated['assigned_to'] ?? null,
                'deadline_date' => $validated['deadline_date'] ?? null,
                'deadline_reason' => $validated['deadline_reason'] ?? null,
                // No requester: nobody asked for this, so there is nobody to
                // acknowledge, chase or tell when it publishes.
                'requester_name' => null,
                'requester_email' => null,
            ]);

            if ($additional->isNotEmpty()) {
                $changeRequest->additionalSites()->sync($additional->all());
            }

            return $changeRequest;
        });

        return redirect()
            ->route('admin.requests.show', $changeRequest)
            ->with('success', "Content {$changeRequest->reference} created. Add a public title to show it on the public list.");
    }
}
