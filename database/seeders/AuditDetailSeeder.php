<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Harvest;
use App\Models\PoolingJob;
use App\Models\Negotiation;
use App\Models\NegotiationMessage;

/**
 * Seeds the minimum records required to render the six audit-skipped
 * detail/edit pages so Lighthouse can measure them:
 *
 *   1. /harvests/{id}/edit                    (farmer)
 *   2. /buyer/crop-board/{harvest}             (buyer)
 *   3. /driver/jobs/{poolingJob}               (driver)
 *   4. /negotiations/{negotiation}             (farmer)
 *   5. /pooling/{poolingJob}                   (logistics)
 *   6. /pooling/{poolingJob}/cost-ledger       (logistics)
 *
 * Idempotent: re-running removes the previously seeded audit records.
 */
class AuditDetailSeeder extends Seeder
{
    private const MARKER = '__audit_seed__';

    public function run(): void
    {
        $this->cleanupPrevious();

        $farmerId  = $this->userId('farmer0@test.com');
        $driverId  = $this->userId('eliseo-driver-1@driver.com');
        $buyerId   = $this->userId('buyer@test.com');

        // ─────────────────────────────────────────────────────
        // 1. Harvest owned by farmer0@test.com (visible to buyers)
        // ─────────────────────────────────────────────────────
        $harvest = Harvest::create([
            'user_id'                    => $farmerId,
            'crop_category_id'           => 1,
            'crop_id'                    => 1,
            'crop_variety_id'            => 1,
            'crop_type'                  => 'Regular Milled Rice',
            'variety'                    => 'Regular Milled',
            'quantity_kg'                => 500.00,
            'unit'                       => 'kg',
            'status'                     => 'active',
            'visibility'                 => 'both',
            'harvest_date'               => now()->addDay()->toDateString(),
            'quality_grade'              => 'Grade A',
            'packaging_type'             => 'Sacks (50kg)',
            'suggested_price_per_kg'     => 45.00,
            'notes'                      => self::MARKER,
            'latitude'                   => 6.22190000,
            'longitude'                  => 125.06640000,
            'destination_id'             => 1,
            'destination_address'        => 'General Santos Public Market',
            'destination_latitude'       => 6.11170000,
            'destination_longitude'      => 125.17100000,
        ]);

        // ─────────────────────────────────────────────────────
        // 2. Pooling job owned by logistics profile #1,
        //    assigned to truck #1 / driver eliseo-driver-1
        // ─────────────────────────────────────────────────────
        $poolingJob = PoolingJob::create([
            'logistics_profile_id' => 1,
            'truck_id'             => 1,
            'driver_id'            => $driverId,
            'buyer_id'             => $buyerId,
            'status'               => 'confirmed',
            'total_kg'             => 500.00,
            'truck_capacity_kg'    => 2500.00,
            'farm_count'           => 1,
            'start_latitude'       => 6.22190000,
            'start_longitude'      => 125.06640000,
            'end_latitude'         => 6.11170000,
            'end_longitude'        => 125.17100000,
            'radius_km'            => 5.00,
            'price_reference'      => 45.00,
            'negotiated_price'     => 45.00,
            'planned_distance_km'  => 12.50,
            'confirmed_at'         => now(),
            'notes'                => self::MARKER,
        ]);

        $poolingJob->harvests()->attach($harvest->id, [
            'pickup_order'   => 1,
            'quantity_kg'    => 500.00,
            'cost_share'     => 1200.00,
            'status'         => 'assigned',
            'payment_status' => 'unpaid',
        ]);

        // ─────────────────────────────────────────────────────
        // 3. Open negotiation between buyer@test.com and farmer0@test.com
        // ─────────────────────────────────────────────────────
        $negotiation = Negotiation::create([
            'buyer_id'                 => $buyerId,
            'farmer_id'                => $farmerId,
            'harvest_id'               => $harvest->id,
            'negotiated_price'         => 46.00,
            'negotiated_volume'        => 500.00,
            'status'                   => 'OPEN',
            'destination_address'      => 'General Santos Public Market',
            'destination_latitude'     => 6.11170000,
            'destination_longitude'    => 125.17100000,
            'last_activity_at'         => now(),
            'buyer_last_read_at'       => now(),
            'farmer_last_read_at'      => now(),
        ]);

        NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $buyerId,
            'message_text'   => 'Hi, we are interested in your Regular Milled Rice at 46.00/kg. Is this available?',
        ]);

        NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $farmerId,
            'message_text'   => 'Yes, 500kg is available for pickup this week.',
        ]);

        $this->command?->info('AuditDetailSeeder done: harvest #'.$harvest->id.', pooling job #'.$poolingJob->id.', negotiation #'.$negotiation->id);
    }

    private function userId(string $email): int
    {
        return DB::table('users')->where('email', $email)->value('id')
            ?? throw new \RuntimeException("User not found: {$email}");
    }

    private function cleanupPrevious(): void
    {
        $harvestIds = DB::table('harvests')->where('notes', self::MARKER)->pluck('id');

        NegotiationMessage::whereIn('negotiation_id', DB::table('negotiations')->whereIn('harvest_id', $harvestIds)->pluck('id'))->delete();
        Negotiation::whereIn('harvest_id', $harvestIds)->forceDelete();
        DB::table('pooling_job_harvests')->whereIn('harvest_id', $harvestIds)->delete();
        DB::table('pooling_jobs')->where('notes', self::MARKER)->delete();
        Harvest::whereIn('id', $harvestIds)->forceDelete();
    }
}
