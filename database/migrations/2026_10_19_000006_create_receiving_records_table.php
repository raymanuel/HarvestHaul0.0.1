<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receiving_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('haul_job_id')->constrained('haul_jobs')->cascadeOnDelete();
            $table->foreignId('cooperative_id')->constrained('cooperatives')->cascadeOnDelete();
            $table->foreignId('farmer_id')->constrained('users')->cascadeOnDelete();

            $table->foreignId('crop_id')->constrained('crops');
            $table->foreignId('crop_variety_id')->nullable()->constrained('crop_varieties')->nullOnDelete();
            $table->foreignId('crop_grade_id')->nullable()->constrained('crop_grades')->nullOnDelete();

            $table->unsignedInteger('actual_sacks')->nullable();
            $table->decimal('actual_weight_kg', 10, 2);
            $table->decimal('buying_price_per_kg', 10, 2);
            $table->decimal('total_amount', 12, 2);

            $table->text('remarks')->nullable();

            $table->foreignId('recorded_by')->constrained('users');
            $table->string('recording_role', 32)->default('field_receiving');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();

            $table->string('status', 32)->default('pending_confirmation')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receiving_records');
    }
};