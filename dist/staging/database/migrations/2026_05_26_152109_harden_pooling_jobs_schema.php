<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            // Safe fallback column verification checks
            if (!Schema::hasColumn('pooling_jobs', 'price_reference')) {
                $table->decimal('price_reference', 10, 2)->nullable()->after('radius_km');
            }
            if (!Schema::hasColumn('pooling_jobs', 'negotiated_price')) {
                $table->decimal('negotiated_price', 10, 2)->nullable()->after('price_reference');
            }
            if (!Schema::hasColumn('pooling_jobs', 'route_geometry')) {
                $table->longText('route_geometry')->nullable()->after('notes');
            }

            // Adjust baseline status to prioritize the Pending Proposal stage
            $table->string('status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->string('status')->default('confirmed')->change();
        });
    }
};
