<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clinical sign-off has to name the text it approved. Without this an approver
     * approves a *request*, so the copy can change afterwards and the record still
     * reads "approved" — which is not a defensible governance trail.
     */
    public function up(): void
    {
        Schema::table('change_request_approvers', function (Blueprint $table) {
            $table->string('approved_content_hash', 64)->nullable()->after('responded_at');
            $table->text('approved_content_snapshot')->nullable()->after('approved_content_hash');
        });
    }

    public function down(): void
    {
        Schema::table('change_request_approvers', function (Blueprint $table) {
            $table->dropColumn(['approved_content_hash', 'approved_content_snapshot']);
        });
    }
};
