<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haul_requests', function (Blueprint $table) {
            $table->decimal('pickup_location_lat', 10, 8)->nullable()->after('pickup_location');
            $table->decimal('pickup_location_lng', 11, 8)->nullable()->after('pickup_location_lat');
        });
    }

    public function down(): void
    {
        Schema::table('haul_requests', function (Blueprint $table) {
            $table->dropColumn(['pickup_location_lat', 'pickup_location_lng']);
        });
    }
};