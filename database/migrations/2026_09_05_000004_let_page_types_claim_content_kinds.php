<?php

use App\Models\CptType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which kinds of content a page type is written into.
     *
     * This lived in config, which meant a code change and a deploy to wire up a
     * page type somebody had just added in the admin. The page types are managed
     * here, so the mapping belongs here too.
     */
    public function up(): void
    {
        Schema::table('cpt_types', function (Blueprint $table) {
            $table->json('content_kinds')->nullable()->after('form_config');
        });

        // Carry over what config said, so nothing changes on the way through.
        foreach (['needs' => ['situation_support', 'appointment_prep'],
                  'services' => ['service_explainer'],
                  'news' => ['announcement', 'campaign']] as $slug => $kinds) {
            CptType::where('slug', $slug)->update(['content_kinds' => json_encode($kinds)]);
        }
    }

    public function down(): void
    {
        Schema::table('cpt_types', function (Blueprint $table) {
            $table->dropColumn('content_kinds');
        });
    }
};
