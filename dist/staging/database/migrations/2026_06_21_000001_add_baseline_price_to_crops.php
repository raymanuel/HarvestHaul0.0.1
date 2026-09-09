<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->decimal('baseline_price_per_kg', 10, 2)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn('baseline_price_per_kg');
        });
    }
};
