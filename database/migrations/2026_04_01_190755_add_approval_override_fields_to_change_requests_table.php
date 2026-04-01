<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->boolean('approval_overridden')->default(false);
            $table->foreignId('approval_overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approval_overridden_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approval_overridden_by');
            $table->dropColumn(['approval_overridden', 'approval_overridden_at']);
        });
    }
};
