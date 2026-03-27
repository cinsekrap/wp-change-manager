<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->date('deadline_date')->nullable()->after('check_answers');
            $table->string('deadline_reason', 500)->nullable()->after('deadline_date');
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn(['deadline_date', 'deadline_reason']);
        });
    }
};
