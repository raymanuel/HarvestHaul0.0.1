<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual production-expense logbook for farmers.
 *
 * HarvestHaul only records transport costs automatically (pooling cost shares
 * and haul-request rates). This table lets farmers log on-farm production
 * expenses (seeds, fertilizer, pesticide, labor, etc.) so the Profit & Expense
 * report can show earnings net of their real costs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category'); // seeds, fertilizer, pesticide, labor, transport, other
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->timestamps();

            $table->index(['user_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_expenses');
    }
};
