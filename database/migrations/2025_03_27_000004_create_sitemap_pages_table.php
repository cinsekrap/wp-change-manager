<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitemap_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->text('url');
            $table->string('url_hash', 64)->storedAs('sha2(url, 256)');
            $table->string('cpt_slug', 100)->default('page');
            $table->string('page_title', 512)->nullable();
            $table->timestamps();

            $table->index(['site_id', 'cpt_slug']);
            $table->unique(['site_id', 'url_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitemap_pages');
    }
};
