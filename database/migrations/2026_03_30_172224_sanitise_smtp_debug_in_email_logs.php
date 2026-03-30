<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Wipe any SMTP debug logs that may contain auth credentials
        DB::table('email_logs')->whereNotNull('smtp_debug')->update(['smtp_debug' => null]);
    }

    public function down(): void
    {
        // Cannot restore stripped data
    }
};
