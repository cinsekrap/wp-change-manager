<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RequestAssigned;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestStatusLog;
use App\Models\EmailLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;

class BulkActionController extends Controller
{
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:change_requests,id',
            'status' => 'required|in:' . implode(',', ChangeRequest::STATUSES),
        ]);

        $newStatus = $request->status;
        $updated = 0;
        $skipped = 0;

        foreach (ChangeRequest::whereIn('id', $request->ids)->get() as $cr) {
            $oldStatus = $cr->status;

            if ($oldStatus === $newStatus) {
                continue;
            }

            // Respect the approval gate
            if (in_array($newStatus, ChangeRequest::POST_REFERRED_STATUSES) && !$cr->canMovePastReferred()) {
                $skipped++;
                continue;
            }

            $updateData = ['status' => $newStatus];

            // Clear rejection reason when not declining/cancelling
            if (!in_array($newStatus, ['declined', 'cancelled'])) {
                $updateData['rejection_reason'] = null;
            }

            $cr->update($updateData);

            // Mark any unresolved items as not done when closing a request
            if (in_array($newStatus, ChangeRequest::TERMINAL_STATUSES)) {
                $cr->items()->where('status', 'in_progress')->update(['status' => 'not_done']);
            }

            ChangeRequestStatusLog::create([
                'change_request_id' => $cr->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            AuditService::log(
                action: 'status_changed',
                model: $cr,
                description: "Bulk status change on {$cr->reference}: {$oldStatus} → {$newStatus}",
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus],
            );

            $updated++;
        }

        $message = "{$updated} request(s) updated.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (outstanding approvals).";
        }

        return response()->json(['success' => true, 'message' => $message, 'updated' => $updated, 'skipped' => $skipped]);
    }

    public function bulkAssign(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:change_requests,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $assignedTo = $request->assigned_to ?: null;
        $assignee = $assignedTo ? User::find($assignedTo) : null;

        $updated = 0;
        foreach (ChangeRequest::whereIn('id', $request->ids)->get() as $cr) {
            $cr->update(['assigned_to' => $assignedTo]);

            $cr->notes()->create([
                'user_id' => auth()->id(),
                'note' => $assignee ? 'Bulk assigned to ' . $assignee->name : 'Bulk unassigned',
            ]);

            if ($assignee && (int) $assignedTo !== auth()->id()) {
                EmailLog::dispatch($assignee->email, new RequestAssigned($cr, $assignee), $cr);
            }

            AuditService::log(
                action: 'assigned',
                model: $cr,
                description: $assignee
                    ? "Bulk assigned {$cr->reference} to {$assignee->name}"
                    : "Bulk unassigned {$cr->reference}",
                newValues: ['assigned_to' => $assignedTo],
            );

            $updated++;
        }

        $message = $assignee
            ? "{$updated} request(s) assigned to {$assignee->name}."
            : "{$updated} request(s) unassigned.";

        return response()->json(['success' => true, 'message' => $message, 'updated' => $updated]);
    }
}
