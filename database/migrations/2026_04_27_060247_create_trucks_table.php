<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('logistics_profile_id')
                  ->constrained('logistics_profiles')
                  ->onDelete('cascade');

            // Default assigned driver for this truck (nullable — truck may be unassigned)
            $table->foreignId('driver_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->string('plate_number')->unique();
            $table->string('truck_name')->nullable();
            $table->decimal('capacity_kg', 10, 2);

            /**
             * available   → Ready to be assigned to a pooling job
             * assigned    → Has a confirmed pooling plan, not yet on the road
             * in_progress → Currently on a delivery run
             * maintenance → Temporarily unavailable
             */
            $table->enum('status', ['available', 'assigned', 'in_progress', 'maintenance'])
                  ->default('available');

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
