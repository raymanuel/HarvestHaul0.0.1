<?php

namespace Tests\Feature;

use App\Models\BuyerOrder;
use App\Models\Cooperative;
use App\Models\Delivery;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryTripExecutionTest extends TestCase
{
    use RefreshDatabase;

    private function setUpTrip(): array
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'in_use']);

        $order = BuyerOrder::create([
            'buyer_id' => $buyer->id, 'cooperative_id' => $coop->id,
            'reference' => 'ORD-TEST', 'status' => BuyerOrder::STATUS_READY_FOR_DELIVERY,
            'total_kg' => 1000, 'total_amount' => 25000,
            'delivery_address' => 'Test address', 'delivery_latitude' => 6.1, 'delivery_longitude' => 125.1,
        ]);

        $job = HaulJob::create([
            'cooperative_id' => $coop->id, 'job_type' => HaulJob::JOB_TYPE_DELIVERY,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $truck->id,
            'pickup_date' => today(), 'status' => HaulJob::STATUS_SCHEDULED,
        ]);
        $stop = HaulJobStop::create([
            'haul_job_id' => $job->id, 'buyer_order_id' => $order->id,
            'sequence_no' => 1, 'status' => HaulJobStop::STATUS_PENDING,
        ]);
        $delivery = Delivery::create([
            'buyer_order_id' => $order->id, 'cooperative_id' => $coop->id,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $truck->id,
            'delivery_date' => today(), 'status' => Delivery::STATUS_SCHEDULED,
        ]);

        return compact('coop', 'driver', 'buyer', 'truck', 'order', 'job', 'stop', 'delivery');
    }

    public function test_marking_stop_delivered_updates_order_delivery_and_completes_trip(): void
    {
        $ctx = $this->setUpTrip();

        $response = $this->actingAs($ctx['driver'])->post(route('delivery.trips.stop-status', [$ctx['stop'], 'delivered']));

        $response->assertRedirect();
        $this->assertEquals(BuyerOrder::STATUS_DELIVERED, $ctx['order']->fresh()->status);
        $this->assertEquals(Delivery::STATUS_DELIVERED, $ctx['delivery']->fresh()->status);
        $this->assertEquals(HaulJob::STATUS_COMPLETED, $ctx['job']->fresh()->status);
        $this->assertEquals('available', $ctx['truck']->fresh()->status);
    }

    public function test_delivery_stop_cannot_use_picked_up_status(): void
    {
        $ctx = $this->setUpTrip();

        $response = $this->actingAs($ctx['driver'])->post(route('delivery.trips.stop-status', [$ctx['stop'], 'picked_up']));

        $response->assertSessionHasErrors('stop');
        $this->assertEquals(HaulJobStop::STATUS_PENDING, $ctx['stop']->fresh()->status);
    }

    public function test_failed_delivery_stop_requires_reason_and_notifies_coop(): void
    {
        $ctx = $this->setUpTrip();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $ctx['coop']->id]);

        $blocked = $this->actingAs($ctx['driver'])->post(route('delivery.trips.stop-status', [$ctx['stop'], 'failed']));
        $blocked->assertSessionHasErrors('reason');

        $response = $this->actingAs($ctx['driver'])->post(route('delivery.trips.stop-status', [$ctx['stop'], 'failed']), [
            'reason' => 'Buyer not available at address.',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'title' => 'Delivery problem reported']);
    }

    public function test_failed_stop_on_trip_completion_reverts_order_to_accepted(): void
    {
        $ctx = $this->setUpTrip();

        $this->actingAs($ctx['driver'])->post(route('delivery.trips.stop-status', [$ctx['stop'], 'arrived']));
        $this->actingAs($ctx['driver'])->post(route('delivery.trips.stop-status', [$ctx['stop'], 'failed']), [
            'reason' => 'Vehicle breakdown.',
        ]);

        $this->assertEquals(HaulJob::STATUS_COMPLETED, $ctx['job']->fresh()->status);
        $this->assertEquals(BuyerOrder::STATUS_ACCEPTED, $ctx['order']->fresh()->status);
    }

    public function test_other_driver_cannot_update_stop(): void
    {
        $ctx = $this->setUpTrip();
        $otherDriver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $ctx['coop']->id]);

        $response = $this->actingAs($otherDriver)->post(route('delivery.trips.stop-status', [$ctx['stop'], 'delivered']));

        $response->assertForbidden();
    }
}
