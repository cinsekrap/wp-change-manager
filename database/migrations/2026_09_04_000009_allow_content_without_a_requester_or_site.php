<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A content designer can start a piece of content themselves. There is no
     * requester — nobody asked for it — and it may not have a home site yet,
     * because "content we want to exist" is a legitimate state before anyone has
     * decided where it lives.
     *
     * Everything arriving through the public wizard still supplies all three.
     */
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->string('requester_name')->nullable()->change();
            $table->string('requester_email')->nullable()->change();
            $table->unsignedBigInteger('site_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->string('requester_name')->nullable(false)->change();
            $table->string('requester_email')->nullable(false)->change();
            $table->unsignedBigInteger('site_id')->nullable(false)->change();
        });
    }
};
