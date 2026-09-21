<?php

namespace Tests\Feature;

use App\Models\BuyerOrder;
use App\Models\Cooperative;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Cooperative\ConsolidationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OutboundConsolidationTest extends TestCase
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

    private function buyer(): User
    {
        return User::factory()->create(['role' => UserRole::BUYER->value]);
    }

    private function acceptedOrder(int $cooperativeId, string $date, float $kg): BuyerOrder
    {
        return BuyerOrder::create([
            'buyer_id' => $this->buyer()->id, 'cooperative_id' => $cooperativeId,
            'reference' => 'ORD-'.uniqid(), 'status' => BuyerOrder::STATUS_ACCEPTED,
            'total_kg' => $kg, 'total_amount' => $kg * 25,
            'preferred_delivery_date' => $date,
            'delivery_address' => 'Test address',
            'delivery_latitude' => 6.12, 'delivery_longitude' => 125.18,
        ]);
    }

    public function test_bin_packing_groups_accepted_orders_by_truck_capacity(): void
    {
        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();

        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 3000, 'status' => 'available']);
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);

        $this->acceptedOrder($coop->id, $date, 3000);
        $this->acceptedOrder($coop->id, $date, 2500);
        $this->acceptedOrder($coop->id, $date, 2000);

        $plan = app(ConsolidationEngine::class)->planDeliveriesForDate($coop, $date);

        $this->assertCount(2, $plan['groups']);
        foreach ($plan['groups'] as $group) {
            $this->assertLessThanOrEqual($group['capacity_kg'], $group['load_kg']);
        }
        $this->assertEmpty($plan['unassigned']);
    }

    public function test_order_too_heavy_for_any_truck_is_unassigned(): void
    {
        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();

        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 3000, 'status' => 'available']);
        $this->acceptedOrder($coop->id, $date, 5000);

        $plan = app(ConsolidationEngine::class)->planDeliveriesForDate($coop, $date);

        $this->assertCount(0, $plan['groups']);
        $this->assertCount(1, $plan['unassigned']);
    }

    public function test_order_without_coordinates_is_excluded(): void
    {
        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 3000, 'status' => 'available']);

        $order = $this->acceptedOrder($coop->id, $date, 1000);
        $order->update(['delivery_latitude' => null, 'delivery_longitude' => null]);

        $plan = app(ConsolidationEngine::class)->planDeliveriesForDate($coop, $date);

        $this->assertCount(0, $plan['groups']);
        $this->assertCount(0, $plan['unassigned']);
    }

    public function test_already_scheduled_order_is_not_replanned(): void
    {
        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 3000, 'status' => 'available']);
        $order = $this->acceptedOrder($coop->id, $date, 1000);

        $job = \App\Models\HaulJob::create([
            'cooperative_id' => $coop->id, 'job_type' => \App\Models\HaulJob::JOB_TYPE_DELIVERY,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $truck->id,
            'pickup_date' => $date, 'status' => \App\Models\HaulJob::STATUS_SCHEDULED,
        ]);
        \App\Models\HaulJobStop::create([
            'haul_job_id' => $job->id, 'buyer_order_id' => $order->id, 'sequence_no' => 1,
            'status' => \App\Models\HaulJobStop::STATUS_PENDING,
        ]);

        $plan = app(ConsolidationEngine::class)->planDeliveriesForDate($coop, $date);

        $this->assertCount(0, $plan['groups']);
        $this->assertCount(0, $plan['unassigned']);
    }

    public function test_plan_attaches_route_geometry_per_group(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok', 'durations' => [[0, 300], [300, 0]], 'distances' => [[0, 5000], [5000, 0]],
            ]),
            'router.project-osrm.org/route/*' => Http::response([
                'code' => 'Ok', 'routes' => [['distance' => 5000, 'duration' => 300, 'geometry' => ['coordinates' => [[125.17, 6.12], [125.18, 6.12]]]]],
            ]),
        ]);

        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 3000, 'status' => 'available']);
        $this->acceptedOrder($coop->id, $date, 1000);

        $plan = app(ConsolidationEngine::class)->planDeliveriesForDate($coop, $date);

        $this->assertNotNull($plan['groups'][0]['route_geometry']);
    }
}
