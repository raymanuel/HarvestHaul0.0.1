<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Harvest.destination_latitude/longitude were never in the model's fillable list,
     * so every posted harvest silently dropped its drop-off coordinates. Copy
     * coordinates back from the farmer's chosen saved destination for rows that
     * still have none. Custom-pinned locations (no destination_id) were lost and
     * cannot be recovered.
     */
    public function up(): void
    {
        DB::statement(
            "UPDATE harvests
             SET destination_latitude = (
                     SELECT d.latitude FROM destinations d
                     WHERE d.id = harvests.destination_id
                       AND d.latitude IS NOT NULL AND d.longitude IS NOT NULL
                     LIMIT 1
                 ),
                 destination_longitude = (
                     SELECT d.longitude FROM destinations d
                     WHERE d.id = harvests.destination_id
                       AND d.latitude IS NOT NULL AND d.longitude IS NOT NULL
                     LIMIT 1
                 )
             WHERE destination_latitude IS NULL
               AND destination_longitude IS NULL
               AND destination_id IS NOT NULL
               AND EXISTS (
                   SELECT 1 FROM destinations d
                   WHERE d.id = harvests.destination_id
                     AND d.latitude IS NOT NULL AND d.longitude IS NOT NULL
               )"
        );
    }

    public function down(): void
    {
        // Data backfill — nothing to roll back.
    }
};