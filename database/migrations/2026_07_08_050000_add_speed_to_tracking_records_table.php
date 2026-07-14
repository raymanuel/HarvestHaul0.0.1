<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracking_records', function (Blueprint $table) {
            $table->decimal('speed_kmh', 8, 2)->nullable()->after('longitude');
            $table->decimal('bearing', 5, 2)->nullable()->after('speed_kmh');
        });
    }

    public function down(): void
    {
        Schema::table('tracking_records', function (Blueprint $table) {
            $table->dropColumn(['speed_kmh', 'bearing']);
        });
    }
};
