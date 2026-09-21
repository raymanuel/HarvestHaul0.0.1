<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haul_jobs', function (Blueprint $table) {
            $table->decimal('route_distance_km', 8, 2)->nullable()->after('scheduled_at');
            $table->decimal('route_duration_min', 8, 2)->nullable()->after('route_distance_km');
            $table->json('route_geometry')->nullable()->after('route_duration_min');
        });
    }

    public function down(): void
    {
        Schema::table('haul_jobs', function (Blueprint $table) {
            $table->dropColumn(['route_distance_km', 'route_duration_min', 'route_geometry']);
        });
    }
};
