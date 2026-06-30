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

            // Foreign Keys
            $table->foreignId('logistics_profile_id')->constrained('logistics_profiles')->cascadeOnDelete();

            // Nullable because a truck can be parked/idle without a driver assigned
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();

            // Truck Details
            $table->string('truck_name');
            $table->string('plate_number')->unique();

            // ADD THIS LINE: To store "Light Truck", "Wing Van", etc.
            $table->string('vehicle_type')->nullable();

            $table->decimal('capacity_kg', 10, 2);
            $table->string('status')->default('available'); // available, in_transit, maintenance

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
