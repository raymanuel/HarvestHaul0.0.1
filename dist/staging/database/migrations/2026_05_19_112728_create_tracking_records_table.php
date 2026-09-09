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
        Schema::create('tracking_records', function (Blueprint $table) {
            $table->id();

            // Relational Foreign Keys
            $table->foreignId('pooling_job_id')->constrained('pooling_jobs')->cascadeOnDelete();

            // Using constrained() but without strict cascading to preserve logs if a driver account is soft-deleted
            $table->foreignId('driver_id')->constrained('users');

            // Geolocation Data (Decimal precision 10,8 is standard for high-accuracy GPS)
            $table->decimal('latitude', 11, 8);
            $table->decimal('longitude', 11, 8);

            // The exact timestamp the browser pushed the coordinate
            $table->timestamp('posted_at');

            // Laravel default created_at / updated_at
            $table->timestamps();

            // Indexing for faster read queries since the coordinator map will poll this table heavily
            $table->index(['pooling_job_id', 'posted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_records');
    }
};
