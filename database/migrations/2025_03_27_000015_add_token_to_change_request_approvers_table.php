<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_request_approvers', function (Blueprint $table) {
            $table->string('token', 64)->nullable()->unique()->after('recorded_by');
        });
    }

    public function down(): void
    {
        Schema::table('change_request_approvers', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
};
