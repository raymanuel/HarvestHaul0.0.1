<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Informational only (spec 15.3 — "may reference haul_request_id/
            // pickup_trip_id/order_id/delivery_id"): a generic type+id pair
            // rather than four separate nullable FKs, set only on the message
            // that opens a new thread so the conversation stays traceable to
            // what prompted it.
            $table->string('context_type', 32)->nullable()->after('cooperative_id');
            $table->unsignedBigInteger('context_id')->nullable()->after('context_type');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['context_type', 'context_id']);
        });
    }
};
