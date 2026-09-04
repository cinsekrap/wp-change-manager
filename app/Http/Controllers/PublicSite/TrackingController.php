<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class TrackingController extends Controller
{
    /** Enough for a person fumbling a reference; far short of working through a range. */
    private const MAX_MISSES_PER_EMAIL = 10;

    /** A backstop for someone rotating addresses, set well above one busy office. */
    private const MAX_MISSES_PER_ADDRESS = 100;

    private const MISS_WINDOW_SECONDS = 3600;

    public function index()
    {
        return view('public.track');
    }

    public function direct(Request $request, string $reference)
    {
        if (!$request->hasValidSignature()) {
            return redirect()->route('tracking')->with('error', 'This link has expired. Please look up your request below.');
        }

        $changeRequest = ChangeRequest::where('reference', $reference)
            ->with(['site', 'statusLogs', 'items'])
            ->withCount('items')
            ->first();

        if (!$changeRequest) {
            return redirect()->route('tracking')->with('error', 'Request not found.');
        }

        return view('public.track-result', compact('changeRequest'));
    }

    public static function signedUrl(ChangeRequest $changeRequest): string
    {
        return \Illuminate\Support\Facades\URL::signedRoute('tracking.direct', [
            'reference' => $changeRequest->reference,
        ]);
    }

    public function show(Request $request)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        // References run in a readable per-day sequence, which is the right trade
        // for something people read down a phone. It does mean the address is
        // the only part of this pair that is hard to arrive at, so failures are
        // counted and eventually refused.
        //
        // Counted against the address, not the caller's IP: ~3,000 colleagues
        // share one address here, so an IP lockout would let any one of them
        // take tracking away from all the others. Someone looking up their own
        // request never reaches the limit, because they are not failing.
        $emailKey = 'track-miss:'.hash('sha256', strtolower($validated['email']));
        $addressKey = 'track-miss-from:'.$request->ip();

        if (RateLimiter::tooManyAttempts($emailKey, self::MAX_MISSES_PER_EMAIL)
            || RateLimiter::tooManyAttempts($addressKey, self::MAX_MISSES_PER_ADDRESS)) {
            return redirect()->route('tracking')
                ->withInput()
                ->with('error', 'Too many attempts. Please wait an hour and try again, or contact the team for your reference.');
        }

        $changeRequest = ChangeRequest::whereRaw('LOWER(reference) = ?', [strtolower($validated['reference'])])
            ->whereRaw('LOWER(requester_email) = ?', [strtolower($validated['email'])])
            ->with(['site', 'statusLogs', 'items'])
            ->withCount('items')
            ->first();

        if (! $changeRequest) {
            RateLimiter::hit($emailKey, self::MISS_WINDOW_SECONDS);
            RateLimiter::hit($addressKey, self::MISS_WINDOW_SECONDS);

            return redirect()->route('tracking')
                ->withInput()
                ->with('error', 'No request found with that reference and email combination.');
        }

        // Only misses are counted, so somebody who mistypes once and then gets it
        // right does not carry the cost of that around for an hour.
        RateLimiter::clear($emailKey);

        return view('public.track-result', compact('changeRequest'));
    }
}
