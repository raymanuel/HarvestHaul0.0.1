<?php

namespace Tests\Feature;

use App\Models\CustomerCard;
use App\Models\OutboundOrder;
use App\Models\PoolingJob;
use App\Models\TrackingRecord;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OutboundEtaDelayGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_outbound_job_skips_eta_and_delay_detection(): void
    {
        $coop = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $coop->logisticsProfile()->create([
            'company_name' => 'GenSan AgCoop', 'business_permit_no' => 'BL-999',
            'phone' => '09171234567', 'is_verified' => true, 'logistics_type' => 'cooperative',
        ]);
        $driver = User::factory()->driver()->create(['email_verified_at' => now()]);
        $driver->driverProfile()->create([
            'partner_id' => $coop->logisticsProfile->id,
            'license_no' => 'DL-' . strtoupper(Str::random(8)),
            'phone' => '09170000002',
        ]);
        $card = CustomerCard::factory()->create(['logistics_profile_id' => $coop->logisticsProfile->id]);
        $order = OutboundOrder::factory()->create([
            'customer_card_id' => $card->id, 'logistics_profile_id' => $coop->logisticsProfile->id,
            'status' => 'confirmed',
        ]);
        $truck = Truck::create([
            'logistics_profile_id' => $coop->logisticsProfile->id, 'truck_name' => 'T1',
            'plate_number' => 'AAA-111', 'capacity_kg' => 2000, 'status' => 'available',
        ]);
        $job = PoolingJob::create([
            'logistics_profile_id' => $coop->logisticsProfile->id, 'truck_id' => $truck->id,
            'driver_id' => $driver->id, 'status' => 'in_progress', 'leg_type' => 'outbound',
            'customer_card_id' => $card->id, 'outbound_order_id' => $order->id,
            'total_kg' => 150, 'truck_capacity_kg' => 2000,
            'start_latitude' => 6.05, 'start_longitude' => 125.13,
            'end_latitude' => 6.10, 'end_longitude' => 125.20,
        ]);

        $response = $this->actingAs($driver)->get(route('driver.jobs.show', $job));
        $response->assertOk();
        $response->assertDontSee('NaN');
    }

    public function test_check_all_active_jobs_skips_outbound_but_keeps_inbound(): void
    {
        $coop = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $coop->logisticsProfile()->create([
            'company_name' => 'GenSan AgCoop', 'business_permit_no' => 'BL-999',
            'phone' => '09171234567', 'is_verified' => true, 'logistics_type' => 'cooperative',
        ]);
        $driver = User::factory()->driver()->create(['email_verified_at' => now()]);
        $driver->driverProfile()->create([
            'partner_id' => $coop->logisticsProfile->id,
            'license_no' => 'DL-' . strtoupper(Str::random(8)),
            'phone' => '09170000003',
        ]);
        $truck = Truck::create([
            'logistics_profile_id' => $coop->logisticsProfile->id, 'truck_name' => 'T1',
            'plate_number' => 'AAA-111', 'capacity_kg' => 2000, 'status' => 'available',
        ]);

        $card = CustomerCard::factory()->create(['logistics_profile_id' => $coop->logisticsProfile->id]);
        $order = OutboundOrder::factory()->create([
            'customer_card_id' => $card->id, 'logistics_profile_id' => $coop->logisticsProfile->id,
            'status' => 'in_transit',
        ]);
        $outboundJob = PoolingJob::create([
            'logistics_profile_id' => $coop->logisticsProfile->id, 'truck_id' => $truck->id,
            'driver_id' => $driver->id, 'status' => 'in_progress', 'leg_type' => 'outbound',
            'customer_card_id' => $card->id, 'outbound_order_id' => $order->id,
            'total_kg' => 150, 'truck_capacity_kg' => 2000,
            'start_latitude' => 6.05, 'start_longitude' => 125.13,
            'end_latitude' => 6.10, 'end_longitude' => 125.20,
        ]);

        $inboundJob = PoolingJob::create([
            'logistics_profile_id' => $coop->logisticsProfile->id, 'truck_id' => $truck->id,
            'driver_id' => $driver->id, 'status' => 'in_progress', 'leg_type' => 'inbound',
            'total_kg' => 100, 'truck_capacity_kg' => 2000,
            'start_latitude' => 6.05, 'start_longitude' => 125.13,
            'end_latitude' => 6.15, 'end_longitude' => 125.25,
        ]);

        $record = fn (int $jobId, int $minutesAgo) => TrackingRecord::create([
            'pooling_job_id' => $jobId,
            'driver_id' => $driver->id,
            'latitude' => 6.05,
            'longitude' => 125.13,
            'speed_kmh' => 0,
            'posted_at' => now()->subMinutes($minutesAgo),
        ]);
        $record($outboundJob->id, 15);
        $record($inboundJob->id, 15);

        $alerts = app(\App\Services\DelayDetectionService::class)->checkAllActiveJobs();
        $alertJobIds = collect($alerts)->pluck('pooling_job_id');

        $this->assertFalse($alertJobIds->contains($outboundJob->id), 'Outbound job should not produce a delay alert.');
        $this->assertTrue($alertJobIds->contains($inboundJob->id), 'Inbound job should still produce a delay alert.');
    }
}
