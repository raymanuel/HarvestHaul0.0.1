<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pooling_job_harvests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pooling_job_id')
                  ->constrained('pooling_jobs')
                  ->onDelete('cascade');

            $table->foreignId('harvest_id')
                  ->constrained('harvests')
                  ->onDelete('cascade');

            $table->integer('pickup_order');          // Sequence in the route (1st stop, 2nd stop, etc.)
            $table->decimal('quantity_kg', 10, 2);   // Snapshot of harvest quantity at time of job
            $table->decimal('distance_from_route', 8, 4)->nullable(); // km off the base route

            $table->timestamps();

            $table->unique(['pooling_job_id', 'harvest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pooling_job_harvests');
    }
};
