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
        Schema::table('pooling_jobs', function (Blueprint $table) {
            // Standardizes string status types to support the pending proposal workflow
            $table->string('status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->string('status')->default('confirmed')->change();
        });
    }
};
