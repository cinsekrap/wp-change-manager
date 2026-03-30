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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('mailable_class');
            $table->string('recipient_email');
            $table->string('subject');
            $table->longText('body_html');
            $table->foreignId('change_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('change_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
