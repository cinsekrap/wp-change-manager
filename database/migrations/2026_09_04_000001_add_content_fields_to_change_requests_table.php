<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            // The page-type tag ("need", "service", "self_help", ...). Indexed because
            // reporting groups by it; the rest of the brief is free-form.
            $table->string('content_type', 40)->nullable()->index()->after('cpt_slug');
            $table->json('content_brief')->nullable()->after('content_type');
            // Written by the content designer, and the only title safe for the public queue —
            // a requester's own words were not written for publication.
            $table->string('public_title', 255)->nullable()->after('content_brief');
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropIndex(['content_type']);
            $table->dropColumn(['content_type', 'content_brief', 'public_title']);
        });
    }
};
