<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additional sites a piece of content is published to. `change_requests.site_id`
     * stays as the main home, so every existing query, email and report is untouched —
     * these rows are purely additive.
     */
    public function up(): void
    {
        Schema::create('change_request_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained();
            // Captured at publish. The URL is never shown on the public queue —
            // the site title is what appears there.
            $table->string('published_url', 2048)->nullable();
            $table->string('published_title', 512)->nullable();
            $table->timestamps();

            $table->unique(['change_request_id', 'site_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_request_sites');
    }
};
