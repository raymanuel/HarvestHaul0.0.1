<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('haul_job_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('haul_job_id')->constrained('haul_jobs')->cascadeOnDelete();
            $table->foreignId('haul_request_id')->constrained('haul_requests')->cascadeOnDelete();

            $table->unsignedInteger('sequence_no');
            $table->string('status', 32)->default('pending')->index();

            $table->timestamp('planned_arrival_at')->nullable();
            $table->timestamp('actual_arrival_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();

            $table->timestamps();

            $table->unique(['haul_job_id', 'sequence_no']);
            $table->unique(['haul_job_id', 'haul_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haul_job_stops');
    }
};