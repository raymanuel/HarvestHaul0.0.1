<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->string('leg_type', 16)->default('inbound')->index();
            $table->foreignId('customer_card_id')->nullable()->after('leg_type')->constrained('customer_cards')->nullOnDelete();
            $table->foreignId('outbound_order_id')->nullable()->after('customer_card_id')->constrained('outbound_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_card_id');
            $table->dropConstrainedForeignId('outbound_order_id');
            $table->dropColumn('leg_type');
        });
    }
};