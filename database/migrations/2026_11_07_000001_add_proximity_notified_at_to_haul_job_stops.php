<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haul_job_stops', function (Blueprint $table) {
            $table->timestamp('proximity_notified_at')->nullable()->after('delay_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('haul_job_stops', function (Blueprint $table) {
            $table->dropColumn('proximity_notified_at');
        });
    }
};
