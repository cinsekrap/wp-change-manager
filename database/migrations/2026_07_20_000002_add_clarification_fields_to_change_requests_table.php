<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->text('clarification_message')->nullable()->after('hold_reason');
            $table->timestamp('clarification_requested_at')->nullable()->after('clarification_message');
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn(['clarification_message', 'clarification_requested_at']);
        });
    }
};
