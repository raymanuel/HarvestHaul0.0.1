<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\HaulRequest;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Cooperative\ConsolidationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PickupConsolidationTest extends TestCase
{
    use RefreshDatabase;

    private function cooperative(): Cooperative
    {
        return Cooperative::create([
            'name'           => 'GenSan AgCoop',
            'type'           => 'primary',
            'contact_number' => '09171234567',
            'official_email' => 'coop@example.com',
            'status'         => Cooperative::STATUS_APPROVED,
            'latitude'       => '6.1164',
            'longitude'      => '125.1716',
        ]);
    }

    private function coopAdmin(Cooperative $cooperative): User
    {
        return User::factory()->create([
            'role'              => UserRole::COOP_ADMIN->value,
            'cooperative_id'    => $cooperative->id,
            'email_verified_at' => now(),
        ]);
    }

    private function approvedRequest(int $cooperativeId, string $date, float $weightKg): HaulRequest
    {
        return HaulRequest::factory()->create([
            'cooperative_id'        => $cooperativeId,
            'status'                => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date,
            'estimated_weight_kg'   => $weightKg,
            'pickup_location_lat'   => 6.12,
            'pickup_location_lng'   => 125.18,
        ]);
    }

    /**
     * groupByCapacity() previously dumped every request into ONE group
     * regardless of fit. This proves real bin-packing: 3 requests that only
     * fit into 2 of 3 available trucks produce 2 groups, none over capacity.
     */
    public function test_bin_packing_produces_multiple_groups_within_capacity(): void
    {
        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();

        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 3000, 'status' => 'available']);
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);

        $this->approvedRequest($coop->id, $date, 3000);
        $this->approvedRequest($coop->id, $date, 2500);
        $this->approvedRequest($coop->id, $date, 2000);

        $plan = app(ConsolidationEngine::class)->planForDate($coop, $date);

        $this->assertCount(2, $plan['groups']);
        foreach ($plan['groups'] as $group) {
            $this->assertLessThanOrEqual($group['capacity_kg'], $group['load_kg']);
        }
        $this->assertEmpty($plan['unassigned']);
    }

    /**
     * A request too heavy for any available truck must be flagged
     * "unassigned" (spec 6.3's "incompatible"), not silently forced in.
     */
    public function test_request_too_heavy_for_any_truck_is_unassigned(): void
    {
        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();

        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 3000, 'status' => 'available']);

        $this->approvedRequest($coop->id, $date, 5000);

        $plan = app(ConsolidationEngine::class)->planForDate($coop, $date);

        $this->assertCount(0, $plan['groups']);
        $this->assertCount(1, $plan['unassigned']);
    }

    public function test_driver_already_booked_that_date_is_excluded_from_available_drivers(): void
    {
        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();

        $busyDriver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $freeDriver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'available']);

        HaulJob::create([
            'haul_request_id' => null,
            'cooperative_id' => $coop->id,
            'delivery_personnel_id' => $busyDriver->id,
            'truck_id' => $truck->id,
            'pickup_date' => $date,
            'status' => HaulJob::STATUS_SCHEDULED,
        ]);

        $available = app(ConsolidationEngine::class)->availableDrivers($coop, $date);

        $this->assertFalse($available->contains('id', $busyDriver->id));
        $this->assertTrue($available->contains('id', $freeDriver->id));
    }

    public function test_store_rejects_a_driver_already_booked_that_date(): void
    {
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        $date = today()->addDay()->toDateString();

        $busyDriver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truckA = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'available']);
        $truckB = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'available']);

        HaulJob::create([
            'haul_request_id' => null,
            'cooperative_id' => $coop->id,
            'delivery_personnel_id' => $busyDriver->id,
            'truck_id' => $truckA->id,
            'pickup_date' => $date,
            'status' => HaulJob::STATUS_SCHEDULED,
        ]);

        $req = $this->approvedRequest($coop->id, $date, 1000);

        $response = $this->actingAs($coopAdmin)->post(route('coop.pickups.store'), [
            'date' => $date,
            'truck_id' => $truckB->id,
            'delivery_personnel_id' => $busyDriver->id,
            'requests' => [$req->id],
        ]);

        $response->assertSessionHasErrors('delivery_personnel_id');
    }

    public function test_store_still_succeeds_for_a_valid_single_group_submission(): void
    {
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        $date = today()->addDay()->toDateString();

        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'available', 'capacity_kg' => 5000]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $req = $this->approvedRequest($coop->id, $date, 2000);

        $response = $this->actingAs($coopAdmin)->post(route('coop.pickups.store'), [
            'date' => $date,
            'truck_id' => $truck->id,
            'delivery_personnel_id' => $driver->id,
            'requests' => [$req->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('haul_requests', ['id' => $req->id, 'status' => HaulRequest::STATUS_SCHEDULED]);
    }

    /**
     * Spec 7.2/8.2: the real route geometry must be visible during planning
     * review, not only persisted silently after trip creation.
     */
    public function test_plan_for_date_attaches_route_geometry_per_group(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok',
                'durations' => [[0, 300], [300, 0]],
                'distances' => [[0, 5000], [5000, 0]],
            ]),
            'router.project-osrm.org/route/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 10000,
                    'duration' => 900,
                    'geometry' => ['coordinates' => [[125.1716, 6.1164], [125.18, 6.12], [125.1716, 6.1164]]],
                ]],
            ]),
        ]);

        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);
        $this->approvedRequest($coop->id, $date, 2000);

        $plan = app(ConsolidationEngine::class)->planForDate($coop, $date);

        $this->assertNotNull($plan['groups'][0]['route_geometry']);
        $this->assertNotEmpty($plan['groups'][0]['route_geometry']['geometry']);
    }

    public function test_plan_for_date_route_geometry_is_null_when_osrm_unreachable(): void
    {
        Http::fake(['router.project-osrm.org/*' => Http::response([], 500)]);

        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);
        $this->approvedRequest($coop->id, $date, 2000);

        $plan = app(ConsolidationEngine::class)->planForDate($coop, $date);

        $this->assertNull($plan['groups'][0]['route_geometry']);
    }

    /**
     * Weight alone must not decide who shares a truck. Two requests ~55km
     * apart both fit one truck by weight (2000+2000=4000 ≤ 5000), but are far
     * outside any reasonable pickup radius of each other — they must land in
     * separate groups, not be force-packed together just because they fit.
     */
    public function test_bin_packing_does_not_group_requests_that_are_too_far_apart(): void
    {
        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();

        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);

        HaulRequest::factory()->create([
            'cooperative_id'        => $coop->id,
            'status'                => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date,
            'estimated_weight_kg'   => 2000,
            'pickup_location_lat'   => 6.12,
            'pickup_location_lng'   => 125.18,
        ]);
        HaulRequest::factory()->create([
            'cooperative_id'        => $coop->id,
            'status'                => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date,
            'estimated_weight_kg'   => 2000,
            'pickup_location_lat'   => 6.62, // ~55km north of the point above
            'pickup_location_lng'   => 125.18,
        ]);

        $plan = app(ConsolidationEngine::class)->planForDate($coop, $date);

        $this->assertCount(2, $plan['groups']);
        foreach ($plan['groups'] as $group) {
            $this->assertCount(1, $group['requests']);
        }
    }
}
