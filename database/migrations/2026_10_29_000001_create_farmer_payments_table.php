<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receiving_record_id')->constrained('receiving_records')->cascadeOnDelete();
            $table->foreignId('cooperative_id')->constrained('cooperatives')->cascadeOnDelete();

            $table->decimal('amount', 12, 2);
            $table->string('method', 32);
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_payments');
    }
};
