<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Copy written into the page type's own fields, rather than one box of text.
     *
     * draft_content stays: it holds the plain rendering of these fields, which is
     * what clinical approval binds to and what the reading-age check reads. A
     * content type with no page type of its own carries no fields here and is
     * still drafted straight into draft_content.
     */
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->json('draft_fields')->nullable()->after('draft_content');
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn('draft_fields');
        });
    }
};
