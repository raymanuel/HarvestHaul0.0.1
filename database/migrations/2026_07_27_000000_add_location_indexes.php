<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // farmer_profiles — nearest-farmer lookups
        Schema::table('farmer_profiles', function (Blueprint $table) {
            $table->index(['latitude', 'longitude']);
        });

        // harvests — drop-off routing
        Schema::table('harvests', function (Blueprint $table) {
            $table->index(['destination_latitude', 'destination_longitude']);
        });

        // pooling_jobs — route start proximity
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->index(['start_latitude', 'start_longitude']);
        });

        // pooling_jobs — route end proximity
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->index(['end_latitude', 'end_longitude']);
        });

        // tracking_records — geofence validation, speed calc
        Schema::table('tracking_records', function (Blueprint $table) {
            $table->index(['latitude', 'longitude']);
        });

        // driver_heartbeats — nearest-driver dispatch
        Schema::table('driver_heartbeats', function (Blueprint $table) {
            $table->index(['latitude', 'longitude']);
        });

        // negotiations — deal location queries
        Schema::table('negotiations', function (Blueprint $table) {
            $table->index(['destination_latitude', 'destination_longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
        });

        Schema::table('harvests', function (Blueprint $table) {
            $table->dropIndex(['destination_latitude', 'destination_longitude']);
        });

        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropIndex(['start_latitude', 'start_longitude']);
        });

        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropIndex(['end_latitude', 'end_longitude']);
        });

        Schema::table('tracking_records', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
        });

        Schema::table('driver_heartbeats', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
        });

        Schema::table('negotiations', function (Blueprint $table) {
            $table->dropIndex(['destination_latitude', 'destination_longitude']);
        });
    }
};
