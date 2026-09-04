<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Files have always hung off a line item. Content requests carry a brief and
     * have no items, so their attachments need somewhere of their own.
     *
     * change_request_item_id becomes nullable and a nullable change_request_id is
     * added alongside it — every existing row keeps its item, and content
     * attachments point straight at the request.
     */
    public function up(): void
    {
        Schema::table('change_request_item_files', function (Blueprint $table) {
            $table->foreignId('change_request_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('change_request_item_files', function (Blueprint $table) {
            $table->unsignedBigInteger('change_request_item_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('change_request_item_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('change_request_id');
        });
    }
};
