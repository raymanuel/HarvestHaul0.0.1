<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_delay_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pooling_job_id')->constrained()->cascadeOnDelete();
            $table->string('alert_type');
            $table->string('severity');
            $table->timestamp('triggered_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['pooling_job_id', 'alert_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_delay_states');
    }
};
