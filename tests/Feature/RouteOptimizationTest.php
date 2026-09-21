<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Cooperative\ConsolidationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RouteOptimizationTest extends TestCase
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

    private function approvedRequest(int $cooperativeId, string $date, float $weightKg, string $windowStart = '08:00', string $windowEnd = '17:00'): HaulRequest
    {
        return HaulRequest::factory()->create([
            'cooperative_id'        => $cooperativeId,
            'status'                => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date,
            'estimated_weight_kg'   => $weightKg,
            'pickup_window_start'   => $windowStart,
            'pickup_window_end'     => $windowEnd,
            'pickup_location_lat'   => 6.12,
            'pickup_location_lng'   => 125.18,
        ]);
    }

    /**
     * Spec 7.8: service duration must scale for large loads, not be a
     * hardcoded flat constant. Uses the advisory (haversine) path so this
     * test has no external network dependency.
     */
    public function test_large_load_gets_longer_service_duration_than_base(): void
    {
        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();

        $normal = $this->approvedRequest($coop->id, $date, 500);
        $large = $this->approvedRequest($coop->id, $date, 3000); // over the 2000kg default threshold

        $schedule = app(ConsolidationEngine::class)->buildScheduleForRequests(
            collect([$normal, $large]),
            $coop
        );

        $normalEntry = collect($schedule)->firstWhere('request_id', $normal->id);
        $largeEntry = collect($schedule)->firstWhere('request_id', $large->id);

        $this->assertNotNull($normalEntry);
        $this->assertNotNull($largeEntry);

        $normalService = $normalEntry['departure_min'] - $normalEntry['arrival_min'] - $normalEntry['wait_min'];
        $largeService = $largeEntry['departure_min'] - $largeEntry['arrival_min'] - $largeEntry['wait_min'];

        $this->assertEquals(15.0, $normalService);
        $this->assertEquals(30.0, $largeService);
    }

    /**
     * Spec 7.7: a stop whose computed arrival lands after its window must be
     * flagged, without aborting the rest of the schedule computation.
     */
    public function test_stop_arriving_after_its_window_is_flagged_not_fatal(): void
    {
        $coop = $this->cooperative();
        $date = today()->addDay()->toDateString();

        // Window closes at 00:01 — arrival (computed from depot travel time) will be after it.
        $impossible = $this->approvedRequest($coop->id, $date, 500, '00:00', '00:01');
        $fine = $this->approvedRequest($coop->id, $date, 500, '00:00', '23:59');

        $schedule = app(ConsolidationEngine::class)->buildScheduleForRequests(
            collect([$impossible, $fine]),
            $coop
        );

        $this->assertCount(2, $schedule);
        $impossibleEntry = collect($schedule)->firstWhere('request_id', $impossible->id);
        $fineEntry = collect($schedule)->firstWhere('request_id', $fine->id);

        $this->assertFalse($impossibleEntry['window_ok']);
        $this->assertTrue($fineEntry['window_ok']);
    }

    public function test_store_persists_route_geometry_when_osrm_succeeds(): void
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

        $job = HaulJob::where('cooperative_id', $coop->id)->firstOrFail();
        $this->assertEquals(10.0, (float) $job->route_distance_km);
        $this->assertEquals(15.0, (float) $job->route_duration_min);
        $this->assertNotNull($job->route_geometry);

        $stop = HaulJobStop::where('haul_job_id', $job->id)->firstOrFail();
        $this->assertNotNull($stop->planned_arrival_at);
    }

    public function test_store_still_creates_trip_when_osrm_is_unreachable(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response([], 500),
        ]);

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

        $job = HaulJob::where('cooperative_id', $coop->id)->firstOrFail();
        $this->assertNull($job->route_geometry);

        // Arrival schedule still computed via the haversine fallback, not blocked.
        $stop = HaulJobStop::where('haul_job_id', $job->id)->firstOrFail();
        $this->assertNotNull($stop->planned_arrival_at);
    }
}
