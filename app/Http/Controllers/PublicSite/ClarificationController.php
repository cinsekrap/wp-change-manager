<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Mail\ClarificationResponded;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestStatusLog;
use App\Models\EmailLog;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ClarificationController extends Controller
{
    public static function respondUrl(ChangeRequest $changeRequest): string
    {
        return URL::signedRoute('respond.show', [
            'reference' => $changeRequest->reference,
        ]);
    }

    public function show(Request $request, string $reference)
    {
        $changeRequest = $this->resolve($request, $reference);

        if (!$changeRequest instanceof ChangeRequest) {
            return $changeRequest;
        }

        if ($changeRequest->status !== 'awaiting_user') {
            return view('public.respond-closed', compact('changeRequest'));
        }

        return view('public.respond', compact('changeRequest'));
    }

    public function store(Request $request, string $reference)
    {
        $changeRequest = $this->resolve($request, $reference);

        if (!$changeRequest instanceof ChangeRequest) {
            return $changeRequest;
        }

        if ($changeRequest->status !== 'awaiting_user') {
            return view('public.respond-closed', compact('changeRequest'));
        }

        $validated = $request->validate([
            'comment' => 'nullable|string|max:5000',
            'items' => 'array',
            'items.*' => 'nullable|string|max:10000',
        ]);

        // Apply edits to this request's own items only
        $changedItems = [];
        foreach ($changeRequest->items as $item) {
            if (!array_key_exists($item->id, $validated['items'] ?? [])) {
                continue;
            }

            $new = trim($validated['items'][$item->id] ?? '');
            if ($new === '' || $new === $item->description) {
                continue;
            }

            $changedItems[$item->id] = ['old' => $item->description, 'new' => $new];
            $item->update(['description' => $new]);
        }

        $comment = trim($validated['comment'] ?? '');

        if ($comment === '' && empty($changedItems)) {
            return redirect()->to(self::respondUrl($changeRequest))
                ->withErrors(['comment' => 'Please add a comment or update at least one change item.'])
                ->withInput();
        }

        $noteLines = [$comment !== ''
            ? 'Requester response to clarification request: ' . $comment
            : 'Requester responded to the clarification request.'];
        if ($changedItems) {
            $noteLines[] = 'Updated ' . count($changedItems) . ' change item(s).';
        }

        $changeRequest->notes()->create([
            'user_id' => null,
            'note' => implode("\n", $noteLines),
        ]);

        if ($changedItems) {
            AuditService::log(
                action: 'updated',
                model: $changeRequest,
                description: "Requester updated change items on {$changeRequest->reference} in response to a clarification request",
                oldValues: array_map(fn ($c) => $c['old'], $changedItems),
                newValues: array_map(fn ($c) => $c['new'], $changedItems),
            );
        }

        // Return the request to where it was before clarification was asked for
        $resumeStatus = $changeRequest->previous_status ?: 'requested';
        $changeRequest->update(['status' => $resumeStatus]);

        ChangeRequestStatusLog::create([
            'change_request_id' => $changeRequest->id,
            'user_id' => null,
            'old_status' => 'awaiting_user',
            'new_status' => $resumeStatus,
        ]);

        // Notify the assignee (or the new-request alert address) that a response arrived
        $notifyEmail = $changeRequest->assignee?->email ?: Setting::get('new_request_alert_email');

        if ($notifyEmail) {
            EmailLog::dispatch($notifyEmail, new ClarificationResponded($changeRequest, $comment, count($changedItems)), $changeRequest);
        } else {
            $changeRequest->notes()->create([
                'user_id' => null,
                'note' => 'Clarification response received — no assignee or alert email configured to notify.',
            ]);
        }

        return view('public.respond-complete', compact('changeRequest'));
    }

    /**
     * Validate the signature and look up the request, or return a redirect.
     */
    private function resolve(Request $request, string $reference)
    {
        if (!$request->hasValidSignature()) {
            return redirect()->route('tracking')->with('error', 'This link has expired. Please look up your request below.');
        }

        $changeRequest = ChangeRequest::where('reference', $reference)
            ->with(['site', 'items', 'cptType', 'assignee'])
            ->first();

        if (!$changeRequest) {
            return redirect()->route('tracking')->with('error', 'Request not found.');
        }

        return $changeRequest;
    }
}
