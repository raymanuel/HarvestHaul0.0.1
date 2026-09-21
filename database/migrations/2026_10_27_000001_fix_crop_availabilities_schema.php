<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crop_availabilities', function (Blueprint $table) {
            $table->decimal('selling_price_per_kg', 10, 2)->nullable()->change();
            $table->unique('receiving_record_id');
        });
    }

    public function down(): void
    {
        Schema::table('crop_availabilities', function (Blueprint $table) {
            $table->dropUnique(['receiving_record_id']);
            $table->decimal('selling_price_per_kg', 10, 2)->nullable(false)->change();
        });
    }
};
