<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Mail\ApprovalRequested;
use App\Mail\NewRequestAlert;
use App\Mail\RequestAssigned;
use App\Mail\RequestSubmitted;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestItem;
use App\Models\ChangeRequestItemFile;
use App\Models\ChangeRequestApprover;
use App\Models\EmailLog;
use App\Models\Setting;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class SubmissionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => ['required', \Illuminate\Validation\Rule::exists('sites', 'id')->where('is_active', true)],
            'page_url' => 'required|string|max:2048',
            'page_title' => 'nullable|string|max:512',
            'cpt_slug' => 'required|string|max:100',
            'is_new_page' => 'boolean',
            'request_type' => 'nullable|in:change,access,content',
            'requester_name' => 'required|string|max:255',
            'requester_email' => 'required|email|max:255',
            'requester_phone' => 'nullable|string|max:50',
            'requester_role' => 'nullable|string|max:255',
            'access_recipient_name' => 'required_if:request_type,access|nullable|string|max:255',
            'access_recipient_email' => 'required_if:request_type,access|nullable|email|max:255',
            'check_answers' => 'nullable|array',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'deadline_date' => 'nullable|date|after:today',
            'deadline_reason' => 'nullable|string|max:500',
            // Content requests carry a brief instead of line items.
            'items' => 'required_unless:request_type,content|array|min:1',
            'content_type' => ['required_if:request_type,content', 'nullable', \Illuminate\Validation\Rule::in(array_keys(config('content-types')))],
            'content_brief' => 'required_if:request_type,content|nullable|array',
            'content_brief.achieve' => 'required_with:content_brief|string|max:5000',
            'content_brief.know_or_do' => 'required_with:content_brief|string|max:5000',
            'content_brief.audience' => 'required_with:content_brief|array|min:1',
            'content_brief.audience.*' => 'string|max:50',
            'content_brief.measure' => 'nullable|string|max:1000',
            'content_brief.already_exists' => 'required_with:content_brief|in:yes,no,not_sure',
            'content_brief.already_exists_detail' => 'nullable|string|max:2000',
            'brief_files' => 'nullable|array|max:5',
            'brief_files.*.filename' => 'required|string',
            'brief_files.*.original_name' => 'required|string',
            'brief_files.*.title' => 'nullable|string|max:255',
            'brief_files.*.description' => 'nullable|string|max:2000',
            'brief_files.*.mime_type' => 'required|string',
            'brief_files.*.file_size' => 'required|integer',
            'additional_site_ids' => 'nullable|array',
            'additional_site_ids.*' => [\Illuminate\Validation\Rule::exists('sites', 'id')->where('is_active', true)],
            'items.*.action_type' => 'required|in:add,change,delete,access_request',
            'items.*.content_area' => 'nullable|string|max:255',
            'items.*.description' => 'required|string|max:5000',
            // A change must capture the current content so the difference is clear.
            'items.*.current_content' => 'required_if:items.*.action_type,change|nullable|string|max:5000',
            'items.*.files' => 'nullable|array|max:5',
            'items.*.files.*.filename' => 'required|string',
            'items.*.files.*.original_name' => 'required|string',
            'items.*.files.*.title' => 'nullable|string|max:255',
            'items.*.files.*.description' => 'nullable|string|max:2000',
            'items.*.files.*.mime_type' => 'required|string',
            'items.*.files.*.file_size' => 'required|integer',
        ], [
            'items.*.current_content.required_if' => 'Please provide the current content for each change so we can see what is different.',
        ]);

        // A change must actually differ from the current content (no-op guard).
        // Compare normalised text so a curly-quote/nbsp-only "difference" — which
        // renders as an empty diff — is still rejected.
        $validator = \Illuminate\Support\Facades\Validator::make([], []);
        foreach (($validated['items'] ?? []) as $i => $item) {
            if (($item['action_type'] ?? null) === 'change' && isset($item['current_content'])) {
                $current = trim(\App\Support\WordDiff::normalize((string) $item['current_content']));
                $updated = trim(\App\Support\WordDiff::normalize((string) $item['description']));
                if ($current === $updated) {
                    $validator->errors()->add("items.{$i}.description", 'The updated content is identical to the current content — please mark up what should change.');
                }
            }
        }
        // Access requests must target a self-service content type
        if (($validated['request_type'] ?? 'change') === 'access') {
            $cptType = \App\Models\CptType::where('slug', $validated['cpt_slug'])->first();
            if (!$cptType || !$cptType->isSelfService()) {
                $validator->errors()->add('cpt_slug', 'Access requests are not available for this content type.');
            }
        }

        if ($validator->errors()->isNotEmpty()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $createdApprovers = [];

        $changeRequest = DB::transaction(function () use ($validated, &$createdApprovers) {
            $reference = ChangeRequest::generateReference();

            $changeRequest = ChangeRequest::create([
                'reference' => $reference,
                'request_type' => $validated['request_type'] ?? 'change',
                'site_id' => $validated['site_id'],
                'page_url' => $validated['page_url'],
                'page_title' => $validated['page_title'] ?? null,
                'cpt_slug' => $validated['cpt_slug'],
                'is_new_page' => $validated['is_new_page'] ?? false,
                'content_type' => $validated['content_type'] ?? null,
                'content_brief' => $validated['content_brief'] ?? null,
                // Content starts as a suggestion: nothing is committed until it is
                // agreed and the hours are funded.
                'status' => ($validated['request_type'] ?? 'change') === 'content' ? 'suggested' : 'requested',
                'priority' => $validated['priority'] ?? 'normal',
                'requester_name' => $validated['requester_name'],
                'requester_email' => $validated['requester_email'],
                'requester_phone' => $validated['requester_phone'] ?? null,
                'requester_role' => $validated['requester_role'] ?? null,
                'access_recipient_name' => $validated['access_recipient_name'] ?? null,
                'access_recipient_email' => $validated['access_recipient_email'] ?? null,
                'check_answers' => $validated['check_answers'] ?? null,
                'deadline_date' => $validated['deadline_date'] ?? null,
                'deadline_reason' => $validated['deadline_reason'] ?? null,
            ]);

            // Additional sites this content should also appear on. site_id stays the
            // main home, so these rows are purely additive.
            if (!empty($validated['additional_site_ids'])) {
                $changeRequest->additionalSites()->sync($validated['additional_site_ids']);
            }

            foreach (($validated['brief_files'] ?? []) as $fileData) {
                if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.\w+$/', $fileData['filename'])) {
                    continue;
                }

                $tempPath = "uploads/temp/{$fileData['filename']}";

                if (!Storage::disk('local')->exists($tempPath)) {
                    continue;
                }

                $fullPath = Storage::disk('local')->path($tempPath);
                $actualSize = filesize($fullPath);
                $actualMime = mime_content_type($fullPath) ?: 'application/octet-stream';

                $permanentPath = "uploads/{$changeRequest->reference}/{$fileData['filename']}";
                Storage::disk('local')->move($tempPath, $permanentPath);

                ChangeRequestItemFile::create([
                    'change_request_id' => $changeRequest->id,
                    'original_filename' => $fileData['original_name'],
                    'title' => $fileData['title'] ?? null,
                    'description' => $fileData['description'] ?? null,
                    'stored_path' => $permanentPath,
                    'mime_type' => $actualMime,
                    'file_size' => $actualSize,
                ]);
            }

            foreach (($validated['items'] ?? []) as $index => $itemData) {
                $item = ChangeRequestItem::create([
                    'change_request_id' => $changeRequest->id,
                    'action_type' => $itemData['action_type'],
                    'content_area' => $itemData['content_area'] ?? null,
                    'description' => $itemData['description'],
                    'current_content' => $itemData['current_content'] ?? null,
                    'sort_order' => $index,
                ]);

                // Move files from temp to permanent storage
                if (!empty($itemData['files'])) {
                    foreach ($itemData['files'] as $fileData) {
                        // Validate filename format
                        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.\w+$/', $fileData['filename'])) {
                            continue;
                        }

                        $tempPath = "uploads/temp/{$fileData['filename']}";

                        if (!Storage::disk('local')->exists($tempPath)) {
                            continue;
                        }

                        // Read actual metadata from file on disk, don't trust client
                        $fullPath = Storage::disk('local')->path($tempPath);
                        $actualSize = filesize($fullPath);
                        $actualMime = mime_content_type($fullPath) ?: 'application/octet-stream';

                        $permanentPath = "uploads/{$changeRequest->reference}/{$fileData['filename']}";
                        Storage::disk('local')->move($tempPath, $permanentPath);

                        ChangeRequestItemFile::create([
                            'change_request_item_id' => $item->id,
                            'original_filename' => $fileData['original_name'],
                            'title' => $fileData['title'] ?? null,
                            'description' => $fileData['description'] ?? null,
                            'stored_path' => $permanentPath,
                            'mime_type' => $actualMime,
                            'file_size' => $actualSize,
                        ]);
                    }
                }
            }

            // Check if auto-referral should proceed
            $site = Site::find($validated['site_id']);
            $checkAnswers = $changeRequest->check_answers ?? [];
            $allChecksPassed = collect($checkAnswers)->every(fn($a) => !empty($a['pass']));
            $canAutoRefer = $allChecksPassed && !$site->requires_approval;

            // Only auto-add approvers and send emails if checks pass and site doesn't require manual approval
            $defaultApprovers = $site->default_approvers ?? [];

            if ($canAutoRefer && !empty($defaultApprovers)) {
                foreach ($defaultApprovers as $approver) {
                    $createdApprovers[] = $changeRequest->approvers()->create([
                        'name' => $approver['name'],
                        'email' => $approver['email'] ?? null,
                        'group' => $approver['group'] ?? null,
                        'token' => ChangeRequestApprover::generateToken(),
                    ]);
                }

                $hasEmailApprovers = collect($defaultApprovers)->contains(fn($a) => !empty($a['email']));
                $changeRequest->update(['status' => $hasEmailApprovers ? 'referred' : 'requires_referral']);
            }
            // If checks failed, stays at 'requested' — admin decides whether to send for approval

            return $changeRequest;
        });

        // Send email notifications
        // A content suggestion is not a submitted change request: it sets a very
        // different expectation about what happens next and how long it takes.
        EmailLog::dispatch(
            $changeRequest->requester_email,
            $changeRequest->isContentRequest()
                ? new \App\Mail\ContentSuggestionReceived($changeRequest)
                : new RequestSubmitted($changeRequest),
            $changeRequest
        );

        // Send approval request emails (only if approvers were auto-added)
        foreach ($createdApprovers as $approver) {
            if ($approver->email && $approver->token) {
                EmailLog::dispatch($approver->email, new ApprovalRequested($changeRequest, $approver), $changeRequest);
            }
        }

        // Auto-assign to the site's default assignee (if configured)
        $site = $changeRequest->site ?? Site::find($changeRequest->site_id);
        if ($site && $site->default_assignee_id) {
            $assignee = User::find($site->default_assignee_id);
            if ($assignee && $assignee->is_active) {
                $changeRequest->update(['assigned_to' => $assignee->id]);
                EmailLog::dispatch($assignee->email, new RequestAssigned($changeRequest, $assignee), $changeRequest);
            }
        }

        // Notify admins about the new request (if configured)
        $alertEmail = Setting::get('new_request_alert_email');
        if ($alertEmail) {
            EmailLog::dispatch($alertEmail, new NewRequestAlert($changeRequest), $changeRequest);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'reference' => $changeRequest->reference,
                'redirect' => URL::signedRoute('confirmation', ['reference' => $changeRequest->reference]),
            ]);
        }

        return redirect(URL::signedRoute('confirmation', ['reference' => $changeRequest->reference]));
    }

    public function confirmation(string $reference)
    {
        $changeRequest = ChangeRequest::where('reference', $reference)
            ->with(['site', 'items'])
            ->firstOrFail();

        return view('public.confirmation', compact('changeRequest'));
    }
}
