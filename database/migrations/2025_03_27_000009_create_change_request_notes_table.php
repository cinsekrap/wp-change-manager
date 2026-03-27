<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_request_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->text('note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_request_notes');
    }
};
