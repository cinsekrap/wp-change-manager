<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Mail\ApprovalRequested;
use App\Mail\RequestAssigned;
use App\Mail\RequestSubmitted;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestItem;
use App\Models\ChangeRequestItemFile;
use App\Models\ChangeRequestApprover;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
            'requester_name' => 'required|string|max:255',
            'requester_email' => 'required|email|max:255',
            'requester_phone' => 'nullable|string|max:50',
            'requester_role' => 'nullable|string|max:255',
            'check_answers' => 'nullable|array',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'deadline_date' => 'nullable|date',
            'deadline_reason' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.action_type' => 'required|in:add,change,delete,access_request',
            'items.*.content_area' => 'nullable|string|max:255',
            'items.*.description' => 'required|string|max:5000',
            'items.*.current_content' => 'nullable|string|max:5000',
            'items.*.files' => 'nullable|array|max:5',
            'items.*.files.*.filename' => 'required|string',
            'items.*.files.*.original_name' => 'required|string',
            'items.*.files.*.mime_type' => 'required|string',
            'items.*.files.*.file_size' => 'required|integer',
        ]);

        $createdApprovers = [];

        $changeRequest = DB::transaction(function () use ($validated, &$createdApprovers) {
            $reference = ChangeRequest::generateReference();

            $changeRequest = ChangeRequest::create([
                'reference' => $reference,
                'site_id' => $validated['site_id'],
                'page_url' => $validated['page_url'],
                'page_title' => $validated['page_title'] ?? null,
                'cpt_slug' => $validated['cpt_slug'],
                'is_new_page' => $validated['is_new_page'] ?? false,
                'status' => 'requested',
                'priority' => $validated['priority'] ?? 'normal',
                'requester_name' => $validated['requester_name'],
                'requester_email' => $validated['requester_email'],
                'requester_phone' => $validated['requester_phone'] ?? null,
                'requester_role' => $validated['requester_role'] ?? null,
                'check_answers' => $validated['check_answers'] ?? null,
                'deadline_date' => $validated['deadline_date'] ?? null,
                'deadline_reason' => $validated['deadline_reason'] ?? null,
            ]);

            foreach ($validated['items'] as $index => $itemData) {
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
        Mail::to($changeRequest->requester_email)->queue(new RequestSubmitted($changeRequest));

        // Send approval request emails (only if approvers were auto-added)
        foreach ($createdApprovers as $approver) {
            if ($approver->email && $approver->token) {
                Mail::to($approver->email)->queue(new ApprovalRequested($changeRequest, $approver));
            }
        }

        // Auto-assign to the site's default assignee (if configured)
        $site = $changeRequest->site ?? Site::find($changeRequest->site_id);
        if ($site && $site->default_assignee_id) {
            $assignee = User::find($site->default_assignee_id);
            if ($assignee && $assignee->is_active) {
                $changeRequest->update(['assigned_to' => $assignee->id]);
                Mail::to($assignee->email)->queue(new RequestAssigned($changeRequest, $assignee));
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'reference' => $changeRequest->reference,
                'redirect' => route('confirmation', $changeRequest->reference),
            ]);
        }

        return redirect()->route('confirmation', $changeRequest->reference);
    }

    public function confirmation(string $reference)
    {
        $changeRequest = ChangeRequest::where('reference', $reference)
            ->with(['site', 'items'])
            ->firstOrFail();

        return view('public.confirmation', compact('changeRequest'));
    }
}
