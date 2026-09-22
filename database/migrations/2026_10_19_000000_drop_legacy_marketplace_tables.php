<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'negotiation_messages',
        'negotiations',
        'haul_intent_messages',
        'haul_intents',
        'outbound_order_lines',
        'outbound_orders',
        'customer_cards',
        'pooling_job_harvests',
        'pooling_jobs',
        'invoices',
        'fuel_logs',
        'job_delay_states',
        'harvests',
        'destinations',
        'haul_requests',
        'logistics_documents',
        'logistics_profiles',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($this->tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        // Legacy marketplace tables are not restored.
    }
};