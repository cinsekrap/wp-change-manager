<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_request_item_files', function (Blueprint $table) {
            $table->string('title')->nullable()->after('original_filename');
            $table->text('description')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('change_request_item_files', function (Blueprint $table) {
            $table->dropColumn(['title', 'description']);
        });
    }
};
