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
        Schema::create('negotiations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('farmer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('harvest_id')->constrained('harvests')->onDelete('cascade');
            $table->decimal('negotiated_price', 10, 2)->nullable();
            $table->decimal('negotiated_volume', 10, 2)->nullable();
            $table->enum('status', ['OPEN', 'AGREED', 'COMPLETED', 'CANCELLED'])->default('OPEN');
            $table->timestamps();
        });

        Schema::create('negotiation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negotiation_id')->constrained('negotiations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('message_text');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('negotiation_messages');
        Schema::dropIfExists('negotiations');
    }
};
