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

class OutboundDispatchTest extends TestCase
{
    use RefreshDatabase;

    private function coop(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name'       => 'GenSan AgCoop',
            'business_permit_no' => 'BL-' . strtoupper(Str::random(6)),
            'phone'              => '09171234567',
            'is_verified'        => true,
            'logistics_type'     => 'cooperative',
        ]);
        return $user;
    }

    private function driverFor(User $coop): User
    {
        $driver = User::factory()->driver()->create(['email_verified_at' => now()]);
        $driver->driverProfile()->create([
            'partner_id'   => $coop->logisticsProfile->id,
            'license_no'   => 'DL-' . strtoupper(Str::random(8)),
            'phone'        => '09170000001',
        ]);
        return $driver;
    }

    private function truckFor(User $coop): Truck
    {
        return Truck::create([
            'logistics_profile_id' => $coop->logisticsProfile->id,
            'truck_name'           => 'Delivery Truck',
            'plate_number'         => 'OUT-1234',
            'capacity_kg'          => 2000,
            'status'               => 'available',
        ]);
    }

    private function orderFor(User $coop): OutboundOrder
    {
        $card = CustomerCard::factory()->create([
            'logistics_profile_id' => $coop->logisticsProfile->id,
            'latitude'             => 6.10,
            'longitude'            => 125.20,
        ]);
        $order = OutboundOrder::factory()->create([
            'customer_card_id'     => $card->id,
            'logistics_profile_id' => $coop->logisticsProfile->id,
            'status'               => 'drafted',
            'total_kg'             => 150,
            'total_amount'         => 3500,
        ]);
        OutboundOrderLine::factory()->create([
            'outbound_order_id' => $order->id,
            'crop_type'         => 'Banana',
            'quantity_kg'       => 150,
            'rate_per_kg'       => 20,
            'subtotal'          => 3000,
        ]);
        return $order;
    }

    public function test_dispatch_creates_outbound_job_with_token(): void
    {
        $coop = $this->coop();
        $driver = $this->driverFor($coop);
        $truck = $this->truckFor($coop);
        $order = $this->orderFor($coop);

        $response = $this->actingAs($coop)->post(route('coop.outbound.dispatch', $order), [
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
            'start_latitude'  => 6.05,
            'start_longitude' => 125.13,
        ]);

        $response->assertRedirect();

        $job = PoolingJob::where('outbound_order_id', $order->id)->first();
        $this->assertNotNull($job);
        $this->assertSame('outbound', $job->leg_type);
        $this->assertSame($coop->logisticsProfile->id, $job->logistics_profile_id);
        $this->assertSame($truck->id, $job->truck_id);
        $this->assertSame($driver->id, $job->driver_id);
        $this->assertEquals(2000, (float) $job->truck_capacity_kg);
        $this->assertSame('confirmed', $job->status->value);

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertNotNull($order->tracking_token);
        $this->assertNotNull($order->dispatched_at);

        $this->assertSame('reserved', $truck->refresh()->status);
        $this->assertFalse($coop->logisticsProfile->availableTrucks()->whereKey($truck->id)->exists());
    }

    public function test_dispatch_refuses_a_truck_that_is_no_longer_available(): void
    {
        $coop = $this->coop();
        $driver = $this->driverFor($coop);
        $truck = $this->truckFor($coop);

        $this->actingAs($coop)->post(route('coop.outbound.dispatch', $this->orderFor($coop)), [
            'truck_id' => $truck->id, 'driver_id' => $driver->id,
            'start_latitude' => 6.05, 'start_longitude' => 125.13,
        ])->assertRedirect();

        $secondOrder = $this->orderFor($coop);
        $this->actingAs($coop)->post(route('coop.outbound.dispatch', $secondOrder), [
            'truck_id' => $truck->id, 'driver_id' => $driver->id,
            'start_latitude' => 6.05, 'start_longitude' => 125.13,
        ])->assertStatus(422);

        $this->assertSame('drafted', $secondOrder->fresh()->status);
    }

    public function test_dispatch_rejects_foreign_driver_or_truck(): void
    {
        $coop = $this->coop();
        $order = $this->orderFor($coop);

        $otherCoop = $this->coop();
        $foreignTruck = $this->truckFor($otherCoop);
        $foreignDriver = $this->driverFor($otherCoop);

        $response = $this->actingAs($coop)->post(route('coop.outbound.dispatch', $order), [
            'truck_id' => $foreignTruck->id,
            'driver_id' => $foreignDriver->id,
            'start_latitude'  => 6.05,
            'start_longitude' => 125.13,
        ]);
        $response->assertSessionHasErrors(['truck_id', 'driver_id']);
    }

    public function test_cannot_dispatch_twice(): void
    {
        $coop = $this->coop();
        $driver = $this->driverFor($coop);
        $truck = $this->truckFor($coop);
        $order = $this->orderFor($coop);

        $this->actingAs($coop)->post(route('coop.outbound.dispatch', $order), [
            'truck_id' => $truck->id, 'driver_id' => $driver->id,
            'start_latitude' => 6.05, 'start_longitude' => 125.13,
        ])->assertRedirect();

        $this->actingAs($coop)->post(route('coop.outbound.dispatch', $order->refresh()), [
            'truck_id' => $truck->id, 'driver_id' => $driver->id,
            'start_latitude' => 6.05, 'start_longitude' => 125.13,
        ])->assertSessionHasErrors('order');
    }
}