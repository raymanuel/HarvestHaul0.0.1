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
        Schema::create('crop_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained()->cascadeOnDelete();
            $table->string('source')->default('da_rfo12');
            $table->date('source_date');
            $table->decimal('price_per_kg', 10, 2);
            $table->timestamps();

            $table->unique(['crop_id', 'source', 'source_date']);
            $table->index(['source', 'source_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_price_history');
    }
};
