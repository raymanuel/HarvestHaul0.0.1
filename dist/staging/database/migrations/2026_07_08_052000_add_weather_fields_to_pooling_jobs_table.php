<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->string('weather_condition', 100)->nullable()->after('route_geometry');
            $table->decimal('weather_temperature', 5, 2)->nullable()->after('weather_condition');
            $table->decimal('weather_wind_speed', 6, 2)->nullable()->after('weather_temperature');
            $table->string('weather_icon', 20)->nullable()->after('weather_wind_speed');
            $table->timestamp('weather_checked_at')->nullable()->after('weather_icon');
            $table->text('weather_advisory')->nullable()->after('weather_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'weather_condition', 'weather_temperature', 'weather_wind_speed',
                'weather_icon', 'weather_checked_at', 'weather_advisory'
            ]);
        });
    }
};
