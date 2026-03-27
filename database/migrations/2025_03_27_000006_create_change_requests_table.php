<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('site_id')->constrained();
            $table->string('page_url', 2048);
            $table->string('page_title', 512)->nullable();
            $table->string('cpt_slug', 100);
            $table->boolean('is_new_page')->default(false);
            $table->string('status', 50)->default('requested');
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('requester_phone', 50)->nullable();
            $table->string('requester_role')->nullable();
            $table->json('check_answers')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('site_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
