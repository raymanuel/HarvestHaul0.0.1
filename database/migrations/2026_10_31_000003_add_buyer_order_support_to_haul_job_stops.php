<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haul_job_stops', function (Blueprint $table) {
            $table->dropUnique(['haul_job_id', 'haul_request_id']);
        });

        Schema::table('haul_job_stops', function (Blueprint $table) {
            $table->foreignId('haul_request_id')->nullable()->change();
            $table->foreignId('buyer_order_id')->nullable()->after('haul_request_id')->constrained('buyer_orders')->cascadeOnDelete();
            $table->timestamp('delivered_at')->nullable()->after('picked_up_at');
        });

        Schema::table('haul_job_stops', function (Blueprint $table) {
            $table->unique(['haul_job_id', 'haul_request_id']);
            $table->unique(['haul_job_id', 'buyer_order_id']);
        });
    }

    public function down(): void
    {
        Schema::table('haul_job_stops', function (Blueprint $table) {
            $table->dropUnique(['haul_job_id', 'haul_request_id']);
            $table->dropUnique(['haul_job_id', 'buyer_order_id']);
            $table->dropConstrainedForeignId('buyer_order_id');
            $table->dropColumn('delivered_at');
        });

        Schema::table('haul_job_stops', function (Blueprint $table) {
            $table->foreignId('haul_request_id')->nullable(false)->change();
        });

        Schema::table('haul_job_stops', function (Blueprint $table) {
            $table->unique(['haul_job_id', 'haul_request_id']);
        });
    }
};
