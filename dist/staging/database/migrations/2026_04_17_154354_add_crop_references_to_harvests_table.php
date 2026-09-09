<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->foreignId('crop_category_id')
                ->nullable()
                ->after('driver_id')
                ->constrained('crop_categories')
                ->nullOnDelete();

            $table->foreignId('crop_id')
                ->nullable()
                ->after('crop_category_id')
                ->constrained('crops')
                ->nullOnDelete();

            $table->foreignId('crop_variety_id')
                ->nullable()
                ->after('crop_id')
                ->constrained('crop_varieties')
                ->nullOnDelete();

            $table->string('unit')->default('kg')->after('quantity_kg');
            $table->date('harvest_date')->nullable();
            $table->string('quality_grade')->nullable();
            $table->string('packaging_type')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('crop_category_id');
            $table->dropConstrainedForeignId('crop_id');
            $table->dropConstrainedForeignId('crop_variety_id');

            $table->dropColumn([
                'unit',
                'harvest_date',
                'quality_grade',
                'packaging_type',
            ]);
        });
    }
};
