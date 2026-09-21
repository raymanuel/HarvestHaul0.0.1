<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_variety_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('low_price_per_kg', 10, 2);
            $table->decimal('high_price_per_kg', 10, 2);
            $table->decimal('common_price_per_kg', 10, 2);
            $table->decimal('dpi_price_per_kg', 10, 2)->nullable();
            $table->string('source', 64)->default('DA RFO12');
            $table->date('price_date');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['crop_id', 'crop_variety_id', 'price_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_prices');
    }
};
