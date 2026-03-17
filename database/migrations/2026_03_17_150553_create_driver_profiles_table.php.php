<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('driver_profiles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

        // This links the Driver to a specific Logistics Partner's company
        $table->foreignId('partner_id')->constrained('partner_profiles')->onDelete('cascade');

        $table->string('license_number')->unique();
        $table->string('vehicle_type')->nullable(); // e.g., 6-wheeler, L300
        $table->enum('employment_status', ['active', 'suspended', 'resigned'])->default('active');

        $table->timestamps();
    });
}

    public function down(): void
{
    Schema::dropIfExists('driver_profiles');
}
};
