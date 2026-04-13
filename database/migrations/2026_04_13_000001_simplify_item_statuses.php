<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert removed statuses to their closest equivalents
        DB::table('change_request_items')->where('status', 'pending')->update(['status' => 'in_progress']);
        DB::table('change_request_items')->where('status', 'deferred')->update(['status' => 'not_done']);

        // Change the column default from 'pending' to 'in_progress'
        Schema::table('change_request_items', function ($table) {
            $table->string('status', 20)->default('in_progress')->change();
        });
    }

    public function down(): void
    {
        Schema::table('change_request_items', function ($table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }
};
