<?php

namespace Tests\Feature;

use App\Models\CustomerCard;
use App\Models\OutboundOrder;
use App\Models\OutboundOrderLine;
use App\Models\PoolingJob;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DriverOutboundTest extends TestCase
{
    use RefreshDatabase;

    private function coop(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name'       => 'GenSan AgCoop', 'business_permit_no' => 'BL-999',
            'phone'              => '09171234567', 'is_verified' => true, 'logistics_type' => 'cooperative',
        ]);
        return $user;
    }

    private function driverFor(User $coop): User
    {
        $driver = User::factory()->driver()->create(['email_verified_at' => now()]);
        $driver->driverProfile()->create([
            'partner_id' => $coop->logisticsProfile->id,
            'license_no' => 'DL-' . strtoupper(Str::random(8)),
            'phone'      => '09170000001',
        ]);
        return $driver;
    }

    private function outboundJobFor(User $coop, User $driver): array
    {
        $card = CustomerCard::factory()->create([
            'logistics_profile_id' => $coop->logisticsProfile->id,
            'name' => 'Robinsons GenSan', 'latitude' => 6.10, 'longitude' => 125.20,
        ]);
        $order = OutboundOrder::factory()->create([
            'customer_card_id' => $card->id, 'logistics_profile_id' => $coop->logisticsProfile->id,
            'status' => 'confirmed', 'tracking_token' => 'tok-a1b2c3d4e5f6', 'total_kg' => 150,
        ]);
        OutboundOrderLine::factory()->create([
            'outbound_order_id' => $order->id, 'crop_type' => 'Banana',
            'quantity_kg' => 150, 'rate_per_kg' => 20, 'subtotal' => 3000,
        ]);
        $truck = Truck::create([
            'logistics_profile_id' => $coop->logisticsProfile->id, 'truck_name' => 'T1',
            'plate_number' => 'AAA-111', 'capacity_kg' => 2000, 'status' => 'available',
        ]);
        $job = PoolingJob::create([
            'logistics_profile_id' => $coop->logisticsProfile->id, 'truck_id' => $truck->id,
            'driver_id' => $driver->id, 'status' => 'confirmed', 'leg_type' => 'outbound',
            'customer_card_id' => $card->id, 'outbound_order_id' => $order->id,
            'total_kg' => 150, 'truck_capacity_kg' => 2000,
            'start_latitude' => 6.05, 'start_longitude' => 125.13,
            'end_latitude' => 6.10, 'end_longitude' => 125.20,
        ]);

        return [$job, $order];
    }

    public function test_driver_sees_outbound_job_with_customer_stop(): void
    {
        $coop = $this->coop();
        $driver = $this->driverFor($coop);
        [$job, $order] = $this->outboundJobFor($coop, $driver);

        $this->actingAs($driver)->get(route('driver.dashboard'))->assertOk();
        $response = $this->actingAs($driver)->get(route('driver.jobs.show', $job));
        $response->assertOk();
        $response->assertSee('Robinsons GenSan');
        $response->assertSee('Banana');
    }

    public function test_driver_cannot_finalize_outbound_before_delivered(): void
    {
        $coop = $this->coop();
        $driver = $this->driverFor($coop);
        [$job] = $this->outboundJobFor($coop, $driver);
        $job->update(['status' => 'in_progress']);

        $response = $this->actingAs($driver)->patch(route('driver.jobs.status', $job), [
            'end_odometer_reading' => 1000,
        ]);
        $response->assertSessionHasErrors('order');
    }

    public function test_driver_marks_delivered_then_finalizes(): void
    {
        $coop = $this->coop();
        $driver = $this->driverFor($coop);
        [$job, $order] = $this->outboundJobFor($coop, $driver);
        $job->update(['status' => 'in_progress']);

        $this->actingAs($driver)->post(route('driver.jobs.outbound-delivered', $job))->assertRedirect();
        $order->refresh();
        $this->assertNotNull($order->delivered_at);

        $this->actingAs($driver)->patch(route('driver.jobs.status', $job->refresh()), [
            'end_odometer_reading' => 1000,
        ])->assertRedirect();

        $this->assertSame('awaiting_confirmation', $job->refresh()->status->value);
        $truck = $job->truck->fresh();
        $this->assertSame('available', $truck->status);
    }

    public function test_unauthorized_driver_cannot_finalize_outbound(): void
    {
        $coop = $this->coop();
        $driver = $this->driverFor($coop);
        $otherDriver = $this->driverFor($coop);
        [$job] = $this->outboundJobFor($coop, $driver);
        $job->update(['status' => 'in_progress']);

        $this->actingAs($otherDriver)->post(route('driver.jobs.outbound-delivered', $job))->assertForbidden();
    }
}
