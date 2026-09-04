<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->decimal('capacity_volume_cubic_m', 8, 2)->nullable()->after('capacity_kg');
        });

        Schema::table('harvests', function (Blueprint $table) {
            $table->decimal('estimated_volume_cubic_m', 8, 2)->nullable()->after('quantity_kg');
        });
    }

    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->dropColumn('capacity_volume_cubic_m');
        });

        Schema::table('harvests', function (Blueprint $table) {
            $table->dropColumn('estimated_volume_cubic_m');
        });
    }
};
