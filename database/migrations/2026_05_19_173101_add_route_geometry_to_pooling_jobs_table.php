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
    Schema::table('pooling_jobs', function (Blueprint $table) {
        // We use JSON to store the long list of [lng, lat] coordinates
        $table->json('route_geometry')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            //
        });
    }
};
