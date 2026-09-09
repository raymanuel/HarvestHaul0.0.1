<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            // Total road distance of the drawn route (km). When present the
            // road-distance cost suggestion is more accurate than the haversine
            // leg sum in planned_distance_km.
            $table->decimal('road_distance_km', 8, 2)->nullable()->after('farm_distances');

            // Road terrain selected at plan time (flat | rolling | mountainous).
            // Used to scale fuel / maintenance costs in the rate suggestion.
            $table->string('terrain', 20)->nullable()->after('road_distance_km');

            // Human-readable label of where the rate suggestion came from.
            $table->string('rate_source', 255)->nullable()->after('terrain');
        });
    }

    public function down(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropColumn(['road_distance_km', 'terrain', 'rate_source']);
        });
    }
};