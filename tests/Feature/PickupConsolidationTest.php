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

    /**
     * A trip can now run past its own pickup_date waiting on the depot-
     * delivery step — a driver still mid-trip from an earlier date must
     * stay excluded from a brand-new trip planned for today/tomorrow, not
     * just from a second trip on that same original date.
     */
    public function test_driver_still_on_an_open_trip_from_an_earlier_date_is_excluded(): void
    {
        $coop = $this->cooperative();
        $busyDriver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'in_use']);

        HaulJob::create([
            'haul_request_id' => null,
            'cooperative_id' => $coop->id,
            'delivery_personnel_id' => $busyDriver->id,
            'truck_id' => $truck->id,
            'pickup_date' => today()->subDay(),
            'status' => HaulJob::STATUS_PICKED_UP,
        ]);

        $available = app(ConsolidationEngine::class)->availableDrivers($coop, today()->addDay()->toDateString());

        $this->assertFalse($available->contains('id', $busyDriver->id));
    }

    public function test_driver_whose_trip_already_completed_is_available_again(): void
    {
        $coop = $this->cooperative();
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'available']);

        HaulJob::create([
            'haul_request_id' => null,
            'cooperative_id' => $coop->id,
            'delivery_personnel_id' => $driver->id,
            'truck_id' => $truck->id,
            'pickup_date' => today()->subDay(),
            'status' => HaulJob::STATUS_COMPLETED,
        ]);

        $available = app(ConsolidationEngine::class)->availableDrivers($coop, today()->addDay()->toDateString());

        $this->assertTrue($available->contains('id', $driver->id));
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

    /**
     * OSRM's /table endpoint returns durations in SECONDS. The matrix must
     * convert to minutes once, at the boundary — every consumer downstream
     * (advisory travel time, arrival schedule, time-window check) trusts
     * the value is already in minutes.
     */
    public function test_advisory_travel_time_converts_osrm_seconds_to_minutes(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok',
                // 1800 seconds (30 real minutes) each way = 3600s round trip = 60 real minutes.
                'durations' => [[0, 1800], [1800, 0]],
                'distances' => [[0, 20000], [20000, 0]],
            ]),
        ]);

        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);
        $this->approvedRequest($coop->id, $date, 2000);

        $plan = app(ConsolidationEngine::class)->planForDate($coop, $date);

        $this->assertSame('1 h 0 m', $plan['groups'][0]['proposed']['travel_time']);
    }

    /**
     * The single-stop case above happens to reveal the seconds/minutes bug,
     * but the leg-summation loop itself (walking $seq by position, not by
     * value) needs a real multi-stop trip to prove it sums every leg
     * exactly once. A fully symmetric matrix makes the total independent
     * of whatever order nearest-neighbor/2-opt pick.
     */
    public function test_advisory_travel_time_sums_every_leg_once_for_a_multi_stop_trip(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok',
                // 1500s (25 real minutes) between every pair — depot(0), stop(1), stop(2).
                'durations' => [[0, 1500, 1500], [1500, 0, 1500], [1500, 1500, 0]],
                'distances' => [[0, 10000, 10000], [10000, 0, 10000], [10000, 10000, 0]],
            ]),
        ]);

        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);
        $this->approvedRequest($coop->id, $date, 1000);
        $this->approvedRequest($coop->id, $date, 1000);

        $plan = app(ConsolidationEngine::class)->planForDate($coop, $date);

        // depot → stop → stop → depot = 3 legs × 25 real minutes = 75 minutes.
        $this->assertSame('1 h 15 m', $plan['groups'][0]['proposed']['travel_time']);
        $this->assertSame(30.0, $plan['groups'][0]['proposed']['distance_km']);
    }

    /**
     * arrival_min/wait_min must be on the SAME clock as the farmer's pickup
     * window (minutes since midnight) — not minutes since the truck left the
     * depot. Mixing those two clocks produced wait times in the hundreds of
     * minutes and cascading wrong arrival times for every later stop.
     */
    public function test_arrival_and_wait_are_computed_on_minutes_since_midnight(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok',
                // 1800s (30 real minutes) each way.
                'durations' => [[0, 1800], [1800, 0]],
                'distances' => [[0, 20000], [20000, 0]],
            ]),
            // Unrelated to this test — fail closed to the documented 'unknown'/1.0
            // (no-op) buffer, so weather doesn't scale the travel time here.
            'api.open-meteo.com/*' => Http::response([], 500),
        ]);

        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);
        // Factory default pickup window is 08:00-10:00 (480-600 min since midnight).
        $this->approvedRequest($coop->id, $date, 1000);

        $plan = app(ConsolidationEngine::class)->planForDate($coop, $date);
        $schedule = $plan['groups'][0]['proposed']['schedule'][0];

        // Dispatch at the platform default (06:00 = 360 min) + 30 min travel = 390.
        $this->assertSame(390.0, $schedule['arrival_min']);
        // Truck arrives before the 08:00 window opens (480) — waits, doesn't
        // arrive "396.7 minutes late" the way the pre-fix bug reported it.
        $this->assertSame(90.0, $schedule['wait_min']);
        $this->assertTrue($schedule['window_ok']);
    }

    /**
     * The bug wasn't only a display glitch — PickupTripController::store()
     * persists HaulJobStop.planned_arrival_at as pickup_date->startOfDay()
     * ->addMinutes(arrival_min), which already assumed arrival_min was
     * minutes-since-midnight. Before this fix it wasn't, so every real trip
     * saved planned_arrival_at around 12:30-1:00 AM regardless of when the
     * truck actually left.
     */
    public function test_created_trip_stop_gets_a_realistic_planned_arrival_time(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok',
                'durations' => [[0, 1800], [1800, 0]],
                'distances' => [[0, 20000], [20000, 0]],
            ]),
            'router.project-osrm.org/route/*' => Http::response([
                'code' => 'Ok',
                'routes' => [['distance' => 20000, 'duration' => 1800, 'geometry' => ['coordinates' => [[125.18, 6.12], [125.1716, 6.1164]]]]],
            ]),
            'api.open-meteo.com/*' => Http::response([], 500),
        ]);

        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        $date = today()->addDay()->toDateString();

        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'available', 'capacity_kg' => 5000]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $req = $this->approvedRequest($coop->id, $date, 2000);

        $this->actingAs($coopAdmin)->post(route('coop.pickups.store'), [
            'date' => $date,
            'truck_id' => $truck->id,
            'delivery_personnel_id' => $driver->id,
            'requests' => [$req->id],
        ]);

        $stop = HaulJob::first()->stops()->first();

        // Dispatch (06:00) + 30 min travel = 06:30 — not ~00:30/01:00.
        $this->assertSame('06:30:00', $stop->planned_arrival_at->format('H:i:s'));
        $this->assertSame($date, $stop->planned_arrival_at->toDateString());
    }

    /**
     * Real-world case: 2-opt found a route 2 minutes shorter overall by
     * visiting the FARTHEST stop first, which pushed two other farmers past
     * their pickup windows. 2-opt must not accept a swap that trades a
     * small distance saving for new window violations.
     */
    public function test_two_opt_does_not_trade_a_farmers_window_for_a_shorter_route(): void
    {
        Http::fake([
            // Real depot/inter-stop durations (minutes) from the reported case.
            // Order: depot=0, Rosa(heaviest, 5000kg)=1, john(3000kg)=2, Ben(2000kg)=3.
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok',
                'durations' => [
                    [0, 48.4, 32.4, 43.8],
                    [50.7, 0, 45.3, 27.2],
                    [32.6, 45.4, 0, 31.7],
                    [44, 27.2, 31.7, 0],
                ],
                'distances' => [
                    [0, 45300, 29000, 38700],
                    [45300, 0, 45300, 27200],
                    [29000, 45300, 0, 31700],
                    [38700, 27200, 31700, 0],
                ],
            ]),
            'api.open-meteo.com/*' => Http::response([], 500),
        ]);

        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 20000, 'status' => 'available']);

        HaulRequest::factory()->create([
            'cooperative_id' => $coop->id, 'status' => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date, 'estimated_weight_kg' => 5000,
            'pickup_window_start' => '13:00', 'pickup_window_end' => '14:25',
            'pickup_location_lat' => 6.37, 'pickup_location_lng' => 124.96,
        ]);
        HaulRequest::factory()->create([
            'cooperative_id' => $coop->id, 'status' => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date, 'estimated_weight_kg' => 3000,
            'pickup_window_start' => '10:00', 'pickup_window_end' => '11:30',
            'pickup_location_lat' => 6.30, 'pickup_location_lng' => 125.14,
        ]);
        HaulRequest::factory()->create([
            'cooperative_id' => $coop->id, 'status' => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date, 'estimated_weight_kg' => 2000,
            'pickup_window_start' => '07:23', 'pickup_window_end' => '09:23',
            'pickup_location_lat' => 6.33, 'pickup_location_lng' => 125.03,
        ]);

        $plan = app(ConsolidationEngine::class)->planForDate($coop, $date);
        $schedule = $plan['groups'][0]['proposed']['schedule'];

        $violations = collect($schedule)->where('window_ok', false)->count();
        $this->assertSame(0, $violations, 'A 2-minute route saving must not be bought with a broken pickup window.');
    }

    /**
     * Real-world follow-up to the 2-opt fix above: the INITIAL route seed
     * (nearest-neighbor) picked John first because he's geographically
     * closest — but John's pickup window (10:00-11:30) is later than Ben's
     * (07:23-09:23). Visiting the nearest farmer first pushed Ben's arrival
     * to 11:11 AM, hours past his window, even though the 2-opt guard was
     * working correctly (there was no shorter route available that also
     * fixed Ben's window, so it had nothing to swap to). The route must be
     * seeded by deadline urgency, not just distance, or an urgent-but-not-
     * nearest farmer gets stranded before 2-opt ever runs.
     */
    public function test_route_is_seeded_by_window_urgency_not_just_distance(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok',
                'durations' => [
                    [0, 48.4, 32.4, 43.8],
                    [50.7, 0, 45.3, 27.2],
                    [32.6, 45.4, 0, 31.7],
                    [44, 27.2, 31.7, 0],
                ],
                'distances' => [
                    [0, 45300, 29000, 38700],
                    [45300, 0, 45300, 27200],
                    [29000, 45300, 0, 31700],
                    [38700, 27200, 31700, 0],
                ],
            ]),
            'api.open-meteo.com/*' => Http::response([], 500),
        ]);

        $coop = $this->cooperative();
        // Matches the reporting cooperative's own radius setting — these 3
        // real farms are 9-21km apart pairwise, just over the 20km platform
        // default, so the fixture must use the same override to faithfully
        // land all 3 in one group like the live case does.
        $coop->update(['max_cluster_radius_km' => 23]);
        $date = today()->addDay()->toDateString();
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 20000, 'status' => 'available']);

        HaulRequest::factory()->create([
            'cooperative_id' => $coop->id, 'status' => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date, 'estimated_weight_kg' => 5000,
            'pickup_window_start' => '13:00', 'pickup_window_end' => '14:25',
            'pickup_location_lat' => 6.37070430, 'pickup_location_lng' => 124.95926150,
        ]);
        HaulRequest::factory()->create([
            'cooperative_id' => $coop->id, 'status' => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date, 'estimated_weight_kg' => 3000,
            'pickup_window_start' => '10:00', 'pickup_window_end' => '11:30',
            'pickup_location_lat' => 6.30031930, 'pickup_location_lng' => 125.13672170,
        ]);
        $ben = HaulRequest::factory()->create([
            'cooperative_id' => $coop->id, 'status' => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date, 'estimated_weight_kg' => 2000,
            'pickup_window_start' => '07:23', 'pickup_window_end' => '09:23',
            'pickup_location_lat' => 6.32891510, 'pickup_location_lng' => 125.03184240,
        ]);

        $plan = app(ConsolidationEngine::class)->planForDate($coop, $date);
        $this->assertCount(1, $plan['groups'], 'Fixture coordinates must land all 3 stops in one group to isolate the sequencing question.');
        $schedule = collect($plan['groups'][0]['proposed']['schedule'])->keyBy('request_id');

        $violations = $schedule->where('window_ok', false)->count();
        $this->assertSame(0, $violations, 'Ben has the tightest window (closes soonest) — he must not be stranded just because he is not the nearest stop.');
        $this->assertTrue($schedule[$ben->id]['window_ok']);
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
    public function test_bin_packing_uses_the_cooperatives_own_radius_when_set(): void
    {
        // ~25km apart — outside the global 20km default, but inside this
        // coop's own wider 30km setting. Proves the per-coop override raises
        // the global default, not just narrows it.
        $coop = $this->cooperative();
        $coop->update(['max_cluster_radius_km' => 30]);
        $date = today()->addDay()->toDateString();

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
            'pickup_location_lat'   => 6.345, // ~25km from the point above
            'pickup_location_lng'   => 125.18,
        ]);

        $plan = app(ConsolidationEngine::class)->planForDate($coop, $date);

        $this->assertCount(1, $plan['groups']);
        $this->assertCount(2, $plan['groups'][0]['requests']);
    }

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
