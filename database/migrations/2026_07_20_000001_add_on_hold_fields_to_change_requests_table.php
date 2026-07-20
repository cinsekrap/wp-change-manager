<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->text('hold_reason')->nullable()->after('rejection_reason');
            $table->string('previous_status', 50)->nullable()->after('status');
            $table->timestamp('sla_paused_at')->nullable()->after('priority');
            $table->unsignedInteger('sla_paused_hours')->default(0)->after('priority');
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn(['hold_reason', 'previous_status', 'sla_paused_at', 'sla_paused_hours']);
        });
    }
};
