<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Approvers left pending on closed requests kept their tokens, so old
     * approval links stayed live and the requests appeared as "outstanding"
     * in approver queues. Closing paths now clear tokens; this tidies
     * existing rows.
     */
    public function up(): void
    {
        DB::table('change_request_approvers')
            ->where('status', 'pending')
            ->whereNotNull('token')
            ->whereIn('change_request_id', function ($query) {
                $query->select('id')
                    ->from('change_requests')
                    ->whereIn('status', ['done', 'declined', 'cancelled']);
            })
            ->update(['token' => null]);
    }

    public function down(): void
    {
        // Data tidy-up only; nothing to restore.
    }
};
