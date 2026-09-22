<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cooperative_id')->constrained('cooperatives')->cascadeOnDelete();

            $table->string('reference', 32)->nullable()->index();
            $table->string('status', 32)->default('pending')->index();
            $table->decimal('total_kg', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->text('delivery_address')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
        });

        Schema::create('buyer_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_order_id')->constrained('buyer_orders')->cascadeOnDelete();
            $table->foreignId('crop_availability_id')->nullable()->constrained('crop_availabilities')->nullOnDelete();

            $table->foreignId('crop_id')->constrained('crops');
            $table->foreignId('crop_variety_id')->nullable()->constrained('crop_varieties')->nullOnDelete();
            $table->foreignId('crop_grade_id')->nullable()->constrained('crop_grades')->nullOnDelete();

            $table->decimal('quantity_kg', 10, 2);
            $table->decimal('rate_per_kg', 10, 2);
            $table->decimal('subtotal', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_order_items');
        Schema::dropIfExists('buyer_orders');
    }
};