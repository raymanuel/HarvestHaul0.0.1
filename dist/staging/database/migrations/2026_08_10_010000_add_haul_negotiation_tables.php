<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Farmer <-> Logistics haul negotiation:
     *  - rate fields on haul_intents (logistics' offer, farmer's counter, agreed rate)
     *  - a message thread per intent so both sides can negotiate in-app.
     */
    public function up(): void
    {
        Schema::table('haul_intents', function (Blueprint $table) {
            $table->decimal('offer_rate_php_per_kg', 10, 2)->nullable()->after('suggested_date');
            $table->decimal('counter_rate_php_per_kg', 10, 2)->nullable()->after('offer_rate_php_per_kg');
            $table->decimal('hauling_rate_php_per_kg', 10, 2)->nullable()->after('counter_rate_php_per_kg');
        });

        Schema::create('haul_intent_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('haul_intent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('message_text');
            $table->timestamps();

            $table->index(['haul_intent_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haul_intent_messages');

        Schema::table('haul_intents', function (Blueprint $table) {
            $table->dropColumn(['offer_rate_php_per_kg', 'counter_rate_php_per_kg', 'hauling_rate_php_per_kg']);
        });
    }
};
