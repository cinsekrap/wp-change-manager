<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * People who want to hear about a suggestion they did not make.
     *
     * Sign-up is public and an email address is all it takes, so nothing is sent
     * until confirmed_at is set by clicking the link in a confirmation email.
     * Without that this is a way to post someone else's address into a mailing list.
     */
    public function up(): void
    {
        Schema::create('change_request_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['change_request_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_request_watchers');
    }
};
