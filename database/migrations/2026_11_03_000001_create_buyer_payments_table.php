<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cooperative_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 32);
            $table->string('reference', 100)->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['buyer_order_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_payments');
    }
};
