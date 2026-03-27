<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_request_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->text('notes')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_request_approvers');
    }
};
