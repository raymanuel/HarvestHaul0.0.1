<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('partner_id');
        });

        // Copy phone values from users to driver_profiles where role = 'driver'
        $drivers = DB::table('users')
            ->where('role', 'driver')
            ->whereNotNull('phone')
            ->get();

        foreach ($drivers as $driver) {
            DB::table('driver_profiles')
                ->where('user_id', $driver->id)
                ->update(['phone' => $driver->phone]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('password');
        });

        // Copy phone values back from driver_profiles to users
        $driverProfiles = DB::table('driver_profiles')
            ->whereNotNull('phone')
            ->get();

        foreach ($driverProfiles as $profile) {
            DB::table('users')
                ->where('id', $profile->user_id)
                ->update(['phone' => $profile->phone]);
        }

        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
