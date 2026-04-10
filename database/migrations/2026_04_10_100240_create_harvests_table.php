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
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();

            // The farmer who posted this listing
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // The driver assigned to pick up this harvest (nullable until assigned)
            $table->foreignId('driver_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->string('crop_type');
            $table->decimal('quantity_kg', 8, 2);

            /**
             * pending   → Farmer posted but not yet reviewed/visible
             * active    → Ready for pickup, visible on logistics map
             * completed → Successfully picked up and delivered
             * cancelled → Farmer cancelled or admin removed
             */
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])
                  ->default('pending');

            $table->text('notes')->nullable(); // Optional pickup instructions

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harvests');
    }
};
