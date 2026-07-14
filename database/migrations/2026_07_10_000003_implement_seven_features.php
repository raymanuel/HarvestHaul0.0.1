<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Weather check — no new columns needed (uses existing weather_logs + pooling_jobs weather fields)

        // 2. Re-approval on recalculation — no new columns needed (uses existing pivot status field)

        // 3. Driver crop confirmation — add crop_confirmed to pivot
        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            if (!Schema::hasColumn('pooling_job_harvests', 'crop_confirmed')) {
                $table->boolean('crop_confirmed')->default(false)->after('farmer_qty_confirmed');
            }
        });

        // 4. Route actual vs planned distance
        Schema::table('pooling_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('pooling_jobs', 'planned_distance_km')) {
                $table->decimal('planned_distance_km', 10, 2)->nullable()->after('radius_km');
            }
            if (!Schema::hasColumn('pooling_jobs', 'actual_distance_km')) {
                $table->decimal('actual_distance_km', 10, 2)->nullable()->after('planned_distance_km');
            }
        });

        // 5. Crop photos at listing
        Schema::table('harvests', function (Blueprint $table) {
            if (!Schema::hasColumn('harvests', 'crop_photos')) {
                $table->json('crop_photos')->nullable()->after('packaging_type');
            }
        });

        // 6. Driver identity verification
        Schema::table('driver_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('driver_profiles', 'identity_verified')) {
                $table->boolean('identity_verified')->default(false)->after('last_shift_ended_at');
            }
            if (!Schema::hasColumn('driver_profiles', 'id_photo_path')) {
                $table->string('id_photo_path')->nullable()->after('identity_verified');
            }
            if (!Schema::hasColumn('driver_profiles', 'selfie_path')) {
                $table->string('selfie_path')->nullable()->after('id_photo_path');
            }
        });

        // 7. ETA confidence — no new columns needed (computed at query time)
    }

    public function down(): void
    {
        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->dropColumn(['crop_confirmed']);
        });
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropColumn(['planned_distance_km', 'actual_distance_km']);
        });
        Schema::table('harvests', function (Blueprint $table) {
            $table->dropColumn(['crop_photos']);
        });
        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->dropColumn(['identity_verified', 'id_photo_path', 'selfie_path']);
        });
    }
};
