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
        Schema::table('logistics_profiles', function (Blueprint $table) {
            $table->decimal('default_hauling_rate', 10, 2)->nullable()->default(1.50)->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_profiles', function (Blueprint $table) {
            $table->dropColumn('default_hauling_rate');
        });
    }
};
