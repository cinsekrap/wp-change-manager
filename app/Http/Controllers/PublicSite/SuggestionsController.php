<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestWatcher;
use App\Models\EmailLog;
use Illuminate\Http\Request;

class SuggestionsController extends Controller
{
    /**
     * There is no sign-in, so a queue entry is a published page. This is the
     * whole of what may appear on it — set once, here, rather than per view.
     *
     * Never shown: who suggested it, the brief, the hours estimate, draft copy,
     * decline reasons, the clinical approver, and the published URLs themselves.
     */
    private const PUBLIC_COLUMNS = [
        'id', 'reference', 'public_title', 'content_type', 'status', 'site_id', 'created_at', 'updated_at',
    ];

    /**
     * A suggestion only becomes public once a content designer has written a
     * public title for it — a requester's own words were not written for
     * publication.
     */
    private function publicQuery()
    {
        return ChangeRequest::query()
            ->select(self::PUBLIC_COLUMNS)
            ->where('request_type', 'content')
            ->whereNotNull('public_title')
            // Declined and cancelled suggestions drop off the list. The suggester is
            // emailed either way, so nobody is left guessing about their own request.
            ->whereNotIn('status', ['declined', 'cancelled'])
            ->with(['site:id,name', 'additionalSites:id,name']);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $entries = $this->publicQuery()
            ->when($search !== '', fn ($q) => $q->where('public_title', 'like', '%'.$search.'%'))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('public.suggestions', [
            'entries' => $entries,
            'search' => $search,
            'contentTypes' => config('content-types'),
        ]);
    }

    /**
     * Backs the "does something like this already exist?" field on the brief.
     * This is the queue's business case: catching a duplicate at the moment
     * someone is about to ask for one.
     */
    public function search(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        if (mb_strlen($search) < 3) {
            return response()->json(['results' => []]);
        }

        $results = $this->publicQuery()
            ->where('public_title', 'like', '%'.$search.'%')
            ->limit(5)
            ->get()
            ->map(fn ($cr) => [
                'reference' => $cr->reference,
                'title' => $cr->public_title,
                'status' => ChangeRequest::statusLabel($cr->status),
                'sites' => $cr->allSites()->pluck('name')->all(),
            ]);

        return response()->json(['results' => $results]);
    }

    public function watch(Request $request, string $reference)
    {
        $validated = $request->validate(['email' => 'required|email|max:255']);

        $changeRequest = $this->publicQuery()->where('reference', $reference)->firstOrFail();

        $watcher = ChangeRequestWatcher::firstOrNew([
            'change_request_id' => $changeRequest->id,
            'email' => $validated['email'],
        ]);

        if ($watcher->confirmed_at) {
            return back()->with('success', 'You are already following that suggestion.');
        }

        $watcher->token = ChangeRequestWatcher::generateToken();
        $watcher->save();

        EmailLog::dispatch($watcher->email, new \App\Mail\WatchConfirmation($changeRequest, $watcher), $changeRequest);

        return back()->with('success', 'Almost there — check your email and confirm you want these updates.');
    }

    public function confirm(string $token)
    {
        $watcher = ChangeRequestWatcher::where('token', $token)->firstOrFail();
        $watcher->update(['confirmed_at' => now()]);

        return redirect()->route('suggestions')->with('success', 'You will now get updates about that suggestion.');
    }

    public function unsubscribe(string $token)
    {
        $watcher = ChangeRequestWatcher::where('token', $token)->firstOrFail();
        $watcher->delete();

        return redirect()->route('suggestions')->with('success', 'You will not get any more updates about that suggestion.');
    }
}
