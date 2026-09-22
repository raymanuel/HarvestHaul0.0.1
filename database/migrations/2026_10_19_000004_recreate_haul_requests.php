<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('haul_requests');

        Schema::create('haul_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cooperative_id')->nullable()->constrained('cooperatives')->nullOnDelete();

            $table->foreignId('crop_id')->constrained('crops');
            $table->foreignId('crop_variety_id')->nullable()->constrained('crop_varieties')->nullOnDelete();
            $table->foreignId('packaging_type_id')->nullable()->constrained('packaging_types')->nullOnDelete();

            $table->unsignedInteger('estimated_sacks')->nullable();
            $table->decimal('estimated_weight_kg', 10, 2);

            $table->date('harvest_date')->nullable();
            $table->date('preferred_pickup_date')->nullable();
            $table->text('pickup_location')->nullable();
            $table->text('notes')->nullable();

            $table->string('status', 32)->default('pending')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haul_requests');
    }
};