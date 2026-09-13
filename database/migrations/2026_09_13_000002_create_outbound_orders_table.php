<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_card_id')->constrained('customer_cards')->onDelete('cascade');
            $table->foreignId('logistics_profile_id')->constrained('logistics_profiles')->onDelete('cascade');
            $table->string('status', 32)->default('drafted')->index();
            $table->string('tracking_token', 64)->nullable()->index();
            $table->decimal('total_kg', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_orders');
    }
};