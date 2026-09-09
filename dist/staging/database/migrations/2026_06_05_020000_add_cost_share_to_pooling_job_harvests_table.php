<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            // Stores each farmer's proportional cost share (their_kg / total_kg × negotiated_price)
            // Computed and stored at job confirmation time for audit trail
            $table->decimal('cost_share', 12, 2)->nullable()->after('distance_from_route');
        });
    }

    public function down(): void
    {
        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->dropColumn('cost_share');
        });
    }
};
