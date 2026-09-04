<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The copy a content designer writes, and the thing clinical approval binds to.
     * Change requests express their content as line items; content requests do not
     * have items, so the draft lives here.
     */
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->longText('draft_content')->nullable()->after('content_brief');
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn('draft_content');
        });
    }
};
