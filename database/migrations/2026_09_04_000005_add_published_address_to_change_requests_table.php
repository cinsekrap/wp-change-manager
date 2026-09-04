<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where the content went live on its main home. Additional sites carry the
     * same pair on the pivot; keeping the home here means the common case needs
     * no join.
     */
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->string('published_url', 2048)->nullable()->after('draft_content');
            $table->string('published_title', 512)->nullable()->after('published_url');
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn(['published_url', 'published_title']);
        });
    }
};
