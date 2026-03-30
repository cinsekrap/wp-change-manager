<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
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

        $changeRequest = ChangeRequest::whereRaw('LOWER(reference) = ?', [strtolower($validated['reference'])])
            ->whereRaw('LOWER(requester_email) = ?', [strtolower($validated['email'])])
            ->with(['site', 'statusLogs', 'items'])
            ->withCount('items')
            ->first();

        if (! $changeRequest) {
            return redirect()->route('tracking')
                ->withInput()
                ->with('error', 'No request found with that reference and email combination.');
        }

        return view('public.track-result', compact('changeRequest'));
    }
}
