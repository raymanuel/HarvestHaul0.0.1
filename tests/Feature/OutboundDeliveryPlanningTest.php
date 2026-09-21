<?php

namespace Tests\Feature;

use App\Models\BuyerOrder;
use App\Models\Cooperative;
use App\Models\Delivery;
use App\Models\HaulJob;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OutboundDeliveryPlanningTest extends TestCase
{
    use RefreshDatabase;

    private function cooperative(): Cooperative
    {
        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
            'latitude' => '6.1164', 'longitude' => '125.1716',
        ]);
    }

    private function coopAdmin(Cooperative $coop): User
    {
        return User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id, 'email_verified_at' => now()]);
    }

    private function acceptedOrder(Cooperative $coop, string $date, float $kg): BuyerOrder
    {
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);

        return BuyerOrder::create([
            'buyer_id' => $buyer->id, 'cooperative_id' => $coop->id,
            'reference' => 'ORD-'.uniqid(), 'status' => BuyerOrder::STATUS_ACCEPTED,
            'total_kg' => $kg, 'total_amount' => $kg * 25,
            'preferred_delivery_date' => $date,
            'delivery_address' => 'Test address',
            'delivery_latitude' => 6.12, 'delivery_longitude' => 125.18,
        ]);
    }

    public function test_store_creates_delivery_trip_with_stops_and_delivery_rows(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response(['code' => 'Ok', 'durations' => [[0, 300], [300, 0]], 'distances' => [[0, 5000], [5000, 0]]]),
            'router.project-osrm.org/route/*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 5000, 'duration' => 300, 'geometry' => ['coordinates' => [[125.17, 6.12]]]]]]),
        ]);

        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);
        $date = today()->addDay()->toDateString();
        $order = $this->acceptedOrder($coop, $date, 2000);

        $response = $this->actingAs($admin)->post(route('coop.outbound.store'), [
            'date' => $date,
            'orders' => [$order->id],
            'truck_id' => $truck->id,
            'delivery_personnel_id' => $driver->id,
        ]);

        $response->assertRedirect();
        $job = HaulJob::first();
        $this->assertNotNull($job);
        $this->assertEquals(HaulJob::JOB_TYPE_DELIVERY, $job->job_type);
        $this->assertEquals('in_use', $truck->fresh()->status);

        $this->assertDatabaseHas('haul_job_stops', ['haul_job_id' => $job->id, 'buyer_order_id' => $order->id]);
        $this->assertDatabaseHas('deliveries', ['buyer_order_id' => $order->id, 'truck_id' => $truck->id, 'delivery_personnel_id' => $driver->id]);
        $this->assertEquals(BuyerOrder::STATUS_READY_FOR_DELIVERY, $order->fresh()->status);
    }

    public function test_store_rejects_order_not_accepted(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);
        $date = today()->addDay()->toDateString();
        $order = $this->acceptedOrder($coop, $date, 2000);
        $order->update(['status' => BuyerOrder::STATUS_SUBMITTED]);

        $response = $this->actingAs($admin)->post(route('coop.outbound.store'), [
            'date' => $date,
            'orders' => [$order->id],
            'truck_id' => $truck->id,
            'delivery_personnel_id' => $driver->id,
        ]);

        $response->assertSessionHasErrors('orders');
        $this->assertDatabaseCount('haul_jobs', 0);
    }

    public function test_store_blocks_truck_already_in_use(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'in_use']);
        $date = today()->addDay()->toDateString();
        $order = $this->acceptedOrder($coop, $date, 2000);

        $response = $this->actingAs($admin)->post(route('coop.outbound.store'), [
            'date' => $date,
            'orders' => [$order->id],
            'truck_id' => $truck->id,
            'delivery_personnel_id' => $driver->id,
        ]);

        $response->assertSessionHasErrors('truck_id');
    }

    public function test_index_lists_pending_orders_and_trips(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $this->acceptedOrder($coop, today()->addDay()->toDateString(), 1000);

        $this->actingAs($admin)->get(route('coop.outbound.index'))->assertOk();
    }

    public function test_create_renders_plan(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);
        $this->acceptedOrder($coop, today()->addDay()->toDateString(), 1000);

        $this->actingAs($admin)->get(route('coop.outbound.create', ['date' => today()->addDay()->toDateString()]))->assertOk();
    }

    public function test_other_cooperative_cannot_view_trip(): void
    {
        $coop = $this->cooperative();
        $otherCoop = Cooperative::create(['name' => 'Other', 'type' => 'primary', 'contact_number' => '09171234567', 'official_email' => 'o@example.com', 'status' => Cooperative::STATUS_APPROVED]);
        $otherAdmin = $this->coopAdmin($otherCoop);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id]);

        $job = HaulJob::create([
            'cooperative_id' => $coop->id, 'job_type' => HaulJob::JOB_TYPE_DELIVERY,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $truck->id,
            'pickup_date' => today(), 'status' => HaulJob::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($otherAdmin)->get(route('coop.outbound.show', $job));

        $response->assertForbidden();
    }
}
