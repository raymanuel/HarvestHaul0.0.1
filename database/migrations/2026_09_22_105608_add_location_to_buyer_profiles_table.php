<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('buyer_profiles', function (Blueprint $table) {
            // Anchor to is_verified (present since create_buyer_profiles_table).
            // Not business_address — that column is added by the Oct 19
            // repurpose migration, which runs AFTER this one (Sep 22). MySQL
            // honors "after", so the old anchor crashed fresh migrations
            // with "Unknown column business_address".
            $table->decimal('latitude', 10, 8)->nullable()->after('is_verified');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('location_label')->nullable()->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buyer_profiles', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_label']);
        });
    }
};
