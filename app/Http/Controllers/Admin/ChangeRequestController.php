<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ApprovalRequested;
use App\Mail\RequestStatusChanged;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestItemFile;
use App\Models\ChangeRequestStatusLog;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ChangeRequest::with('site')->withCount('items');

        if ($request->filled('status')) {
            $statuses = (array) $request->status;
            $query->whereIn('status', $statuses);
        }

        if ($request->filled('site_id')) {
            $siteIds = (array) $request->site_id;
            $query->whereIn('site_id', $siteIds);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('requester_name', 'like', "%{$search}%")
                  ->orWhere('requester_email', 'like', "%{$search}%")
                  ->orWhere('page_url', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $requests = $query->latest()->paginate(25)->withQueryString();
        $sites = Site::orderBy('name')->get();

        return view('admin.requests.index', compact('requests', 'sites'));
    }

    public function show(ChangeRequest $changeRequest)
    {
        $changeRequest->load(['site', 'items.files', 'notes.user', 'statusLogs.user', 'approvers.recordedByUser']);

        $pageHistory = ChangeRequest::where('page_url', $changeRequest->page_url)
            ->where('site_id', $changeRequest->site_id)
            ->where('id', '!=', $changeRequest->id)
            ->latest()
            ->take(10)
            ->get();

        $activities = collect();

        foreach ($changeRequest->notes as $note) {
            $activities->push((object) [
                'type' => 'note',
                'date' => $note->created_at,
                'user' => $note->user->name,
                'note' => $note->note,
            ]);
        }

        foreach ($changeRequest->statusLogs as $log) {
            $activities->push((object) [
                'type' => 'status',
                'date' => $log->created_at,
                'user' => $log->user?->name ?? 'System',
                'old_status' => $log->old_status,
                'new_status' => $log->new_status,
            ]);
        }

        foreach ($changeRequest->approvers->where('status', '!=', 'pending') as $approver) {
            $activities->push((object) [
                'type' => 'approval',
                'date' => $approver->responded_at ?? $approver->updated_at,
                'user' => $approver->name,
                'approval_status' => $approver->status,
                'notes' => $approver->notes,
            ]);
        }

        $activities->push((object) [
            'type' => 'created',
            'date' => $changeRequest->created_at,
            'user' => $changeRequest->requester_name,
        ]);

        $activities = $activities->sortBy('date');

        return view('admin.requests.show', compact('changeRequest', 'pageHistory', 'activities'));
    }

    public function updateStatus(Request $request, ChangeRequest $changeRequest)
    {
        $rules = [
            'status' => 'required|in:' . implode(',', ChangeRequest::STATUSES),
        ];

        if (in_array($request->status, ['declined', 'cancelled'])) {
            $rules['rejection_reason'] = 'required|string|max:2000';
        }

        $request->validate($rules);

        $oldStatus = $changeRequest->status;
        $newStatus = $request->status;

        if ($oldStatus !== $newStatus) {
            // Block moving past "referred" if approvals are outstanding
            $postReferredStatuses = ['approved', 'scheduled', 'done'];
            if (in_array($newStatus, $postReferredStatuses) && !$changeRequest->canMovePastReferred()) {
                return back()->with('error', 'Cannot move to "' . ucfirst($newStatus) . '" — there are outstanding approvals.');
            }

            $updateData = ['status' => $newStatus];

            if (in_array($newStatus, ['declined', 'cancelled'])) {
                $updateData['rejection_reason'] = $request->rejection_reason;
            } else {
                $updateData['rejection_reason'] = null;
            }

            $changeRequest->update($updateData);

            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            // Notify the requester of the status change
            Mail::to($changeRequest->requester_email)
                ->queue(new RequestStatusChanged($changeRequest, $oldStatus, $newStatus));
        }

        return back()->with('success', 'Status updated.');
    }

    public function addNote(Request $request, ChangeRequest $changeRequest)
    {
        $request->validate([
            'note' => 'required|string|max:5000',
        ]);

        $changeRequest->notes()->create([
            'user_id' => auth()->id(),
            'note' => $request->note,
        ]);

        return back()->with('success', 'Note added.');
    }

    public function addApprover(Request $request, ChangeRequest $changeRequest)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $approver = $changeRequest->approvers()->create([
            'name' => $request->name,
            'email' => $request->email,
            'token' => ChangeRequestApprover::generateToken(),
        ]);

        // Send approval request email if approver has an email
        if ($approver->email && $approver->token) {
            Mail::to($approver->email)->queue(new ApprovalRequested($changeRequest, $approver));
        }

        $changeRequest->notes()->create([
            'user_id' => auth()->id(),
            'note' => 'Added approver: ' . $request->name,
        ]);

        // Pull back to "requires_referral" if currently past it
        $postReferred = ['approved', 'scheduled', 'done'];
        if (in_array($changeRequest->status, $postReferred)) {
            $oldStatus = $changeRequest->status;
            $changeRequest->update(['status' => 'requires_referral']);

            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'requires_referral',
            ]);

            return back()->with('success', 'Approver added. Status moved back to Referred pending approval.');
        }

        return back()->with('success', 'Approver added.');
    }

    public function updateApprover(Request $request, ChangeRequest $changeRequest, ChangeRequestApprover $approver)
    {
        abort_unless($approver->change_request_id === $changeRequest->id, 404);

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:1000',
            'responded_at' => 'required|date',
        ]);

        $approver->update([
            'status' => $request->status,
            'notes' => $request->notes,
            'responded_at' => $request->responded_at,
            'recorded_by' => auth()->id(),
        ]);

        // Auto-advance to "approved" if all approvers have approved
        $changeRequest->refresh();
        if ($changeRequest->approvalsAllApproved() && in_array($changeRequest->status, ['requires_referral', 'referred'])) {
            $oldStatus = $changeRequest->status;
            $changeRequest->update(['status' => 'approved']);

            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'approved',
            ]);

            // Notify the requester their request was approved
            Mail::to($changeRequest->requester_email)
                ->queue(new RequestStatusChanged($changeRequest, $oldStatus, 'approved'));

            return back()->with('success', 'Approval recorded. All approvers approved — status moved to Approved.');
        }

        return back()->with('success', 'Approval recorded.');
    }

    public function removeApprover(ChangeRequest $changeRequest, ChangeRequestApprover $approver)
    {
        abort_unless($approver->change_request_id === $changeRequest->id, 404);

        $name = $approver->name;
        $approver->delete();

        $changeRequest->notes()->create([
            'user_id' => auth()->id(),
            'note' => 'Removed approver: ' . $name,
        ]);

        return back()->with('success', 'Approver removed.');
    }

    public function downloadFile(ChangeRequest $changeRequest, ChangeRequestItemFile $file)
    {
        $itemIds = $changeRequest->items()->pluck('id');
        if (!$itemIds->contains($file->change_request_item_id)) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($file->stored_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->download($file->stored_path, $file->original_filename);
    }
}
