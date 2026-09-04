<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->time('pickup_window_start')->nullable()->after('harvest_date');
            $table->time('pickup_window_end')->nullable()->after('pickup_window_start');
        });

        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->time('delivery_deadline')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->dropColumn(['pickup_window_start', 'pickup_window_end']);
        });

        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropColumn('delivery_deadline');
        });
    }
};