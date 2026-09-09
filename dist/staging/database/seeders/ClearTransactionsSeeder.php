<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClearTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'notification_preferences',
            'notifications',
            'farmer_expenses',
            'fuel_logs',
            'invoices',
            'haul_intent_messages',
            'haul_intents',
            'haul_requests',
            'weather_logs',
            'driver_schedules',
            'driver_heartbeats',
            'tracking_records',
            'pooling_job_harvests',
            'pooling_jobs',
            'trucks',
            'negotiation_messages',
            'negotiations',
            'destinations',
            'crop_varieties',
            'crops',
            'crop_categories',
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
            $this->command->info("Truncated: {$table}");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('All transactional data cleared. Users preserved.');
    }
}
