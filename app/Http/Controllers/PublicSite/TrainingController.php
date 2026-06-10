<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Mail\TrainingConfirmed;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestStatusLog;
use App\Models\EmailLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function show(string $token)
    {
        $changeRequest = ChangeRequest::where('training_token', $token)
            ->with(['site', 'cptType'])
            ->firstOrFail();

        if ($changeRequest->training_confirmed_at) {
            return view('public.training-complete', ['changeRequest' => $changeRequest, 'alreadyConfirmed' => true]);
        }

        if (in_array($changeRequest->status, ['declined', 'cancelled'])) {
            return view('public.training-closed', compact('changeRequest'));
        }

        return view('public.training', compact('changeRequest'));
    }

    public function confirm(Request $request, string $token)
    {
        $request->validate([
            'confirmed' => 'accepted',
        ], [
            'confirmed.accepted' => 'Please confirm you have watched the training video and feel competent.',
        ]);

        $changeRequest = ChangeRequest::where('training_token', $token)
            ->with(['site', 'cptType'])
            ->firstOrFail();

        if ($changeRequest->training_confirmed_at) {
            return view('public.training-complete', ['changeRequest' => $changeRequest, 'alreadyConfirmed' => true]);
        }

        if (in_array($changeRequest->status, ['declined', 'cancelled'])) {
            return view('public.training-closed', compact('changeRequest'));
        }

        $changeRequest->update(['training_confirmed_at' => now()]);

        if ($changeRequest->status === 'training') {
            $changeRequest->update(['status' => 'trained']);

            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => null,
                'old_status' => 'training',
                'new_status' => 'trained',
            ]);
        }

        // Notify the assignee (or the new-request alert address) so they can grant access
        $notifyEmail = $changeRequest->assignee?->email ?: Setting::get('new_request_alert_email');

        if ($notifyEmail) {
            EmailLog::dispatch($notifyEmail, new TrainingConfirmed($changeRequest), $changeRequest);
        } else {
            $changeRequest->notes()->create([
                'user_id' => null,
                'note' => 'Training confirmed by ' . $changeRequest->access_recipient_name . ' — no assignee or alert email configured to notify.',
            ]);
        }

        return view('public.training-complete', ['changeRequest' => $changeRequest, 'alreadyConfirmed' => false]);
    }
}
