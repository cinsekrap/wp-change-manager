<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a content designer works out at Sized Up and what the funding decision
     * turns on. The tool already told requesters we would "estimate the hours" and
     * described Awaiting Funding as waiting for them, while having nowhere to put
     * the number.
     *
     * Internal only. It is never shown to a requester and never leaves the admin.
     */
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->decimal('estimated_hours', 6, 1)->nullable()->after('content_brief');
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn('estimated_hours');
        });
    }
};
