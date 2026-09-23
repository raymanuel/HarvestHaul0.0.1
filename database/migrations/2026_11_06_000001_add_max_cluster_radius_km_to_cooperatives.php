<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperatives', function (Blueprint $table) {
            // Null = use the platform default (config('harvesthaul.consolidation.max_cluster_radius_km')).
            // A cooperative can widen or tighten pickup-consolidation grouping
            // distance for its own operating area.
            $table->decimal('max_cluster_radius_km', 6, 2)->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('cooperatives', function (Blueprint $table) {
            $table->dropColumn('max_cluster_radius_km');
        });
    }
};
