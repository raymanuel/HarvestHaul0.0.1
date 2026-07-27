<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('harvest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('preferred_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending, accepted, expired
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_requests');
    }
};
