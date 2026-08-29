<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Independent-farmer flow additions:
     *  1. haul_requests.buyer_id            → track which buyer owns the crop being hauled
     *  2. pooling_jobs.hauling_rate_per_kg  → flat logistics hauling rate (₱/kg) quoted per job
     *  3. pooling_job_harvests.amount_paid  → fix latent bug: read/written but never created
     */
    public function up(): void
    {
        Schema::table('haul_requests', function (Blueprint $table) {
            $table->foreignId('buyer_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->decimal('hauling_rate_per_kg', 10, 2)
                ->nullable()
                ->after('negotiated_price');
        });

        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->decimal('amount_paid', 12, 2)->nullable()->after('cost_share');
        });
    }

    public function down(): void
    {
        Schema::table('haul_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('buyer_id');
        });

        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropColumn('hauling_rate_per_kg');
        });

        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
        });
    }
};
