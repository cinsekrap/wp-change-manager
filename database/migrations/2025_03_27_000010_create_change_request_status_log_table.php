<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_request_status_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('old_status', 50);
            $table->string('new_status', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_request_status_log');
    }
};
