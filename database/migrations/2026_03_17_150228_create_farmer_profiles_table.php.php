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
    Schema::create('farmer_profiles', function (Blueprint $table) {
        $table->id();
        // Links to users.id. If user is deleted, this profile is deleted.
        $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
        $table->string('phone')->nullable();
        $table->boolean('is_verified')->default(false); // Controlled by Admin
        $table->text('farm_location')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('farmer_profiles');
    }
};
