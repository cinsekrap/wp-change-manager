<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_request_items', function (Blueprint $table) {
            $table->text('current_content')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('change_request_items', function (Blueprint $table) {
            $table->dropColumn('current_content');
        });
    }
};
