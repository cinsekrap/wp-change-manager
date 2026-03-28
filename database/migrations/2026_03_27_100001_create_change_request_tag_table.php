<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_request_tag', function (Blueprint $table) {
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['change_request_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_request_tag');
    }
};
