<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->enum('payment_status', ['unpaid', 'submitted', 'paid'])->default('unpaid')->after('cost_share');
            $table->string('receipt_path')->nullable()->after('payment_status');
            $table->string('delivery_receipt_path')->nullable()->after('receipt_path');
            $table->decimal('loaded_quantity_kg', 10, 2)->nullable()->after('delivery_receipt_path');
            $table->decimal('loaded_volume_cubic_meters', 10, 2)->nullable()->after('loaded_quantity_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'receipt_path',
                'delivery_receipt_path',
                'loaded_quantity_kg',
                'loaded_volume_cubic_meters'
            ]);
        });
    }
};
