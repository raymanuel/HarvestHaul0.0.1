<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haul_job_stops', function (Blueprint $table) {
            $table->timestamp('delay_notified_at')->nullable()->after('actual_arrival_at');
            $table->string('pod_photo_path')->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('haul_job_stops', function (Blueprint $table) {
            $table->dropColumn(['delay_notified_at', 'pod_photo_path']);
        });
    }
};
