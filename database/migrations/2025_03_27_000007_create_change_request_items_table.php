<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->string('action_type', 20);
            $table->string('content_area')->nullable();
            $table->text('description');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_request_items');
    }
};
