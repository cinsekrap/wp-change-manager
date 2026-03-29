<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cpt_types', function (Blueprint $table) {
            $table->string('request_mode', 20)->default('normal')->after('is_active');
            $table->renameColumn('blocked_message', 'mode_message');
        });

        // Migrate data: is_blocked = true → request_mode = 'blocked'
        DB::table('cpt_types')->where('is_blocked', true)->update(['request_mode' => 'blocked']);

        Schema::table('cpt_types', function (Blueprint $table) {
            $table->dropColumn('is_blocked');
        });
    }

    public function down(): void
    {
        Schema::table('cpt_types', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('is_active');
            $table->renameColumn('mode_message', 'blocked_message');
        });

        DB::table('cpt_types')->where('request_mode', 'blocked')->update(['is_blocked' => true]);

        Schema::table('cpt_types', function (Blueprint $table) {
            $table->dropColumn('request_mode');
        });
    }
};
