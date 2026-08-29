<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('haul_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('haul_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('logistics_profile_id')->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->date('suggested_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haul_intents');
    }
};
