<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The named people who may sign off content as clinically safe.
     *
     * Site default_approvers are free text typed into a form, which is fine for
     * "who signs off a wording change on this site" and not fine for a clinical
     * record — a hash bound to a name somebody typed proves very little. Content
     * approvers are chosen from this managed list instead.
     */
    public function up(): void
    {
        Schema::create('clinical_approvers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('job_title')->nullable();
            // Free text, deliberately not a fixed taxonomy: the point is to help a
            // content designer judge whether this is the right person to ask.
            $table->text('areas_of_expertise')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_approvers');
    }
};
