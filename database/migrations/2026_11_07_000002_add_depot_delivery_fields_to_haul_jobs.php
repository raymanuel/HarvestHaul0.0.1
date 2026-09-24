<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haul_jobs', function (Blueprint $table) {
            $table->timestamp('depot_delivered_at')->nullable()->after('completed_at');
            $table->string('depot_pod_photo_path')->nullable()->after('depot_delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('haul_jobs', function (Blueprint $table) {
            $table->dropColumn(['depot_delivered_at', 'depot_pod_photo_path']);
        });
    }
};
