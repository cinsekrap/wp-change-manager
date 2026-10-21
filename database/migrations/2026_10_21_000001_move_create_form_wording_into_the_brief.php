<?php

use App\Models\ChangeRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The create content form saved what was typed into it as the draft. In
     * practice it holds a summary or wording pasted from old content — something
     * to work from, not the copy — so the form now keeps it with the brief. This
     * moves what the form saved before.
     *
     * Only text nobody can have worked on as copy moves: content the team started
     * itself (the form is the only way in without a requester), never written into
     * the page type's fields, never sent for clinical approval, and either not yet
     * being written or of a kind whose fields kept it hidden. Anything else may be
     * real copy by now and stays the draft.
     */
    public function up(): void
    {
        $candidates = ChangeRequest::query()
            ->where('request_type', 'content')
            ->whereNull('requester_email')
            ->whereNotNull('draft_content')
            ->where('draft_content', '!=', '')
            ->whereNull('draft_fields')
            ->whereDoesntHave('approvers')
            ->get();

        foreach ($candidates as $request) {
            $untouched = in_array($request->status, ['suggested', 'scoped', 'awaiting_funding'], true)
                || $request->hasStructuredDraft();

            if (! $untouched) {
                continue;
            }

            $brief = $request->content_brief ?? [];
            $brief['existing_wording'] = $request->draft_content;

            // Straight to the table: a model save of draft_content runs the
            // approval-voiding hook, which has nothing to do with a move.
            DB::table('change_requests')->where('id', $request->id)->update([
                'content_brief' => json_encode($brief),
                'draft_content' => null,
            ]);
        }
    }

    public function down(): void
    {
        // Data move only; the form now saves this wording to the brief.
    }
};
