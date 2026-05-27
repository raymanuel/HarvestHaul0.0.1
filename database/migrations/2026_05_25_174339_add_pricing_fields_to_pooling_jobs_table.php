<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            // System-calculated estimate for coordination
            $table->decimal('price_reference', 12, 2)->nullable()->after('radius_km');

            // Final price agreed upon via in-app messaging
            $table->decimal('negotiated_price', 12, 2)->nullable()->after('price_reference');
        });
    }

    public function down(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropColumn(['price_reference', 'negotiated_price']);
        });
    }
};
