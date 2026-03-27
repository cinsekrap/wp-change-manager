<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_request_item_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_item_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_request_item_files');
    }
};
