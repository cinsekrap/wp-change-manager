<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('change_request_tag');
        Schema::dropIfExists('tags');
    }

    public function down(): void
    {
        Schema::create('tags', function ($table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('colour', 7)->default('#6E6E6D');
            $table->timestamps();
        });

        Schema::create('change_request_tag', function ($table) {
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['change_request_id', 'tag_id']);
        });
    }
};
