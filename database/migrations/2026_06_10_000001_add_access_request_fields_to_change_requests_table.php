<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->string('request_type', 20)->default('change')->after('reference')->index();
            $table->string('access_recipient_name')->nullable()->after('requester_role');
            $table->string('access_recipient_email')->nullable()->after('access_recipient_name');
            $table->string('training_token', 64)->nullable()->after('access_recipient_email')->index();
            $table->timestamp('training_sent_at')->nullable()->after('training_token');
            $table->timestamp('training_confirmed_at')->nullable()->after('training_sent_at');
        });

        // Mark pre-existing self-service access requests so they render with the new UI
        DB::table('change_requests')
            ->where('page_url', 'self-service-access-request')
            ->update([
                'request_type' => 'access',
                'access_recipient_name' => DB::raw('requester_name'),
                'access_recipient_email' => DB::raw('requester_email'),
            ]);
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn([
                'request_type', 'access_recipient_name', 'access_recipient_email',
                'training_token', 'training_sent_at', 'training_confirmed_at',
            ]);
        });
    }
};
