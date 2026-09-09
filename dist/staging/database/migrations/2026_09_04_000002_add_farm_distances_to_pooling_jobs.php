<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->json('farm_distances')->nullable()->after('route_geometry');
        });
    }

    public function down(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropColumn('farm_distances');
        });
    }
};
