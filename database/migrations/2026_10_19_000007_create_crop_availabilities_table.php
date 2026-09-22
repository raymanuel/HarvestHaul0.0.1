<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->foreignId('receiving_record_id')->constrained('receiving_records')->cascadeOnDelete();

            $table->foreignId('crop_id')->constrained('crops');
            $table->foreignId('crop_variety_id')->nullable()->constrained('crop_varieties')->nullOnDelete();
            $table->foreignId('crop_grade_id')->nullable()->constrained('crop_grades')->nullOnDelete();

            $table->decimal('quantity_kg', 10, 2);
            $table->decimal('sold_kg', 10, 2)->default(0);
            $table->decimal('selling_price_per_kg', 10, 2);

            $table->string('status', 32)->default('available')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_availabilities');
    }
};