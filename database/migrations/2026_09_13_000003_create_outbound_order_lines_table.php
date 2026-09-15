<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_order_id')->constrained('outbound_orders')->onDelete('cascade');
            $table->string('crop_type');
            $table->decimal('quantity_kg', 10, 2);
            $table->decimal('rate_per_kg', 10, 2);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_order_lines');
    }
};