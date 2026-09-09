<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            // Use DECIMAL for kg — supports fractional weights and large quantities
            $table->decimal('quantity_kg', 10, 2)->unsigned()->change();
            // 10 digits total, 2 decimal places → max: 99,999,999.99 kg
        });
    }

    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->decimal('quantity_kg', 8, 2)->unsigned()->change();
        });
    }
};
