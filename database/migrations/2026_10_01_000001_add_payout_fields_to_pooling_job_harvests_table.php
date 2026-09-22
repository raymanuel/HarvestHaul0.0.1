<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->decimal('other_deductions', 12, 2)->nullable()->after('amount_paid');
            $table->timestamp('paid_at')->nullable()->after('other_deductions');
        });
    }

    public function down(): void
    {
        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->dropColumn(['other_deductions', 'paid_at']);
        });
    }
};