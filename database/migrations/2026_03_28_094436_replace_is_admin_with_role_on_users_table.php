<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->after('is_admin');
        });

        // Migrate data: is_admin = true → role = 'super_admin', is_admin = false → role = null
        DB::table('users')->where('is_admin', true)->update(['role' => 'super_admin']);
        DB::table('users')->where('is_admin', false)->update(['role' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('is_active');
        });

        DB::table('users')->where('role', 'super_admin')->update(['is_admin' => true]);
        DB::table('users')->where('role', 'editor')->update(['is_admin' => true]);
        DB::table('users')->whereNull('role')->update(['is_admin' => false]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
