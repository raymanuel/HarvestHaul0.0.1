<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_order_id')->constrained('buyer_orders')->cascadeOnDelete();
            $table->foreignId('cooperative_id')->constrained('cooperatives')->cascadeOnDelete();

            $table->foreignId('delivery_personnel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('truck_id')->nullable()->constrained('trucks')->nullOnDelete();

            $table->date('delivery_date')->nullable();
            $table->timestamp('scheduled_at')->nullable();

            $table->string('status', 32)->default('scheduled')->index();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};