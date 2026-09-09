<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // crop_categories — add description if missing (already has status)
        Schema::table('crop_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('crop_categories', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
        });

        // crops — add description column (scientific_name and handling_notes already exist)
        Schema::table('crops', function (Blueprint $table) {
            if (!Schema::hasColumn('crops', 'description')) {
                $table->text('description')->nullable()->after('scientific_name');
            }
        });

        // crop_varieties — add price_per_kg (the critical missing column)
        Schema::table('crop_varieties', function (Blueprint $table) {
            if (!Schema::hasColumn('crop_varieties', 'price_per_kg')) {
                $table->decimal('price_per_kg', 10, 2)->unsigned()->default(0.00)->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('crop_categories', function (Blueprint $table) {
            if (Schema::hasColumn('crop_categories', 'description')) {
                $table->dropColumn('description');
            }
        });

        Schema::table('crops', function (Blueprint $table) {
            if (Schema::hasColumn('crops', 'description')) {
                $table->dropColumn('description');
            }
        });

        Schema::table('crop_varieties', function (Blueprint $table) {
            if (Schema::hasColumn('crop_varieties', 'price_per_kg')) {
                $table->dropColumn('price_per_kg');
            }
        });
    }
};
