<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haul_requests', function (Blueprint $table) {
            $table->time('pickup_window_start')->nullable()->after('preferred_pickup_date');
            $table->time('pickup_window_end')->nullable()->after('pickup_window_start');
        });
    }

    public function down(): void
    {
        Schema::table('haul_requests', function (Blueprint $table) {
            $table->dropColumn(['pickup_window_start', 'pickup_window_end']);
        });
    }
};