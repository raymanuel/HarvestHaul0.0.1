<?php

namespace Tests\Feature;

use App\Models\CustomerCard;
use App\Models\OutboundOrder;
use App\Models\PoolingJob;
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
}
