<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('buyer_id')->nullable()->after('driver_id');
            $table->foreign('buyer_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->timestamp('buyer_confirmed_at')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropForeign(['buyer_id']);
            $table->dropColumn('buyer_id');
        });

        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->dropColumn('buyer_confirmed_at');
        });
    }
};
