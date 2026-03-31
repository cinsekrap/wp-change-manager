<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Wipe SMTP debug logs that contain auth credentials.
     * The stripping regex in EmailLog::dispatch was not matching
     * timestamped lines, so credentials were stored since v1.2.9.
     */
    public function up(): void
    {
        DB::table('email_logs')->whereNotNull('smtp_debug')->update(['smtp_debug' => null]);
    }

    public function down(): void
    {
        // Cannot restore stripped data
    }
};
