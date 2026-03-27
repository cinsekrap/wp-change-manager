<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('remember_token');
            $table->string('provider', 50)->nullable()->after('is_active');
            $table->string('provider_id', 255)->nullable()->after('provider');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'provider', 'provider_id']);
        });
    }
};
