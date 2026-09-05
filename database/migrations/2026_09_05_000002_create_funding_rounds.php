<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Funding is agreed for a batch, not a piece at a time: someone with a budget
     * is asked for a number covering several pieces at once. That is why this is
     * not the per-request approver table — which exists to bind a clinician to a
     * version of the copy, and would send a budget holder one email per item.
     */
    public function up(): void
    {
        Schema::create('funding_approvers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('job_title')->nullable();
            // Free text on purpose, as with clinical expertise: what budget they
            // hold is a sentence, not a taxonomy.
            $table->text('remit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('funding_rounds', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();

            // The approver's details are copied in, so the record still says who
            // was asked after the managed list changes.
            $table->foreignId('funding_approver_id')->nullable()->constrained()->nullOnDelete();
            $table->string('approver_name');
            $table->string('approver_email');

            $table->string('status')->default('pending');
            $table->string('token')->nullable()->unique();

            // What they were shown. An estimate edited afterwards does not
            // silently become something that was approved.
            $table->decimal('total_hours', 8, 1);

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('funding_round_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funding_round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->decimal('estimated_hours', 6, 1)->nullable();
            $table->timestamps();

            $table->unique(['funding_round_id', 'change_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funding_round_items');
        Schema::dropIfExists('funding_rounds');
        Schema::dropIfExists('funding_approvers');
    }
};
