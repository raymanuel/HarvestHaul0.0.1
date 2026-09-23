<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\HaulRequest;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PickupPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function cooperative(): Cooperative
    {
        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary', 'contact_number' => '09171234567',
            'official_email' => 'coop@example.com', 'status' => Cooperative::STATUS_APPROVED,
            'latitude' => '6.1164', 'longitude' => '125.1716',
        ]);
    }

    private function coopAdmin(Cooperative $coop): User
    {
        return User::factory()->create([
            'role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id, 'email_verified_at' => now(),
        ]);
    }

    private function approvedRequest(int $cooperativeId, string $date, float $weightKg): HaulRequest
    {
        return HaulRequest::factory()->create([
            'cooperative_id' => $cooperativeId, 'status' => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date, 'estimated_weight_kg' => $weightKg,
            'pickup_location_lat' => 6.12, 'pickup_location_lng' => 125.18,
        ]);
    }

    public function test_preview_returns_weight_total_without_a_truck_selected(): void
    {
        Http::fake(['router.project-osrm.org/*' => Http::response([], 500)]);

        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $date = today()->addDay()->toDateString();
        $req = $this->approvedRequest($coop->id, $date, 2000);

        $response = $this->actingAs($admin)->postJson(route('coop.pickups.preview'), [
            'date' => $date,
            'requests' => [$req->id],
        ]);

        $response->assertOk();
        $response->assertJson(['load_kg' => 2000.0, 'capacity_kg' => null, 'over_capacity' => false]);
    }

    public function test_preview_flags_over_capacity_for_selected_truck(): void
    {
        Http::fake(['router.project-osrm.org/*' => Http::response([], 500)]);

        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $date = today()->addDay()->toDateString();
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'available', 'capacity_kg' => 3000]);
        $req = $this->approvedRequest($coop->id, $date, 4000);

        $response = $this->actingAs($admin)->postJson(route('coop.pickups.preview'), [
            'date' => $date,
            'requests' => [$req->id],
            'truck_id' => $truck->id,
        ]);

        $response->assertOk();
        $response->assertJson(['load_kg' => 4000.0, 'capacity_kg' => 3000.0, 'over_capacity' => true]);
    }

    public function test_preview_computes_route_and_schedule_when_osrm_available(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok',
                'durations' => [[0, 600], [600, 0]],
                'distances' => [[0, 10000], [10000, 0]],
            ]),
            'router.project-osrm.org/route/*' => Http::response([
                'code' => 'Ok',
                'routes' => [['distance' => 20000, 'duration' => 1200, 'geometry' => ['coordinates' => [[125.18, 6.12], [125.1716, 6.1164]]]]],
            ]),
            'api.open-meteo.com/*' => Http::response([], 500),
        ]);

        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $date = today()->addDay()->toDateString();
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'available', 'capacity_kg' => 5000]);
        $req = $this->approvedRequest($coop->id, $date, 2000);

        $response = $this->actingAs($admin)->postJson(route('coop.pickups.preview'), [
            'date' => $date,
            'requests' => [$req->id],
            'truck_id' => $truck->id,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['load_kg', 'capacity_kg', 'over_capacity', 'distance_km', 'travel_time', 'windows_ok', 'schedule', 'route_geometry']);
        $this->assertNotEmpty($response->json('route_geometry'));
    }

    public function test_preview_rejects_a_request_belonging_to_another_cooperative(): void
    {
        Http::fake(['router.project-osrm.org/*' => Http::response([], 500)]);

        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $otherCoop = Cooperative::create([
            'name' => 'Other Coop', 'type' => 'primary', 'contact_number' => '09170000000',
            'official_email' => 'other@example.com', 'status' => Cooperative::STATUS_APPROVED,
        ]);
        $date = today()->addDay()->toDateString();
        $foreignReq = $this->approvedRequest($otherCoop->id, $date, 1000);

        $response = $this->actingAs($admin)->postJson(route('coop.pickups.preview'), [
            'date' => $date,
            'requests' => [$foreignReq->id],
        ]);

        $response->assertStatus(422);
    }

    public function test_farmer_cannot_access_the_preview_endpoint(): void
    {
        $farmer = User::factory()->farmer()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($farmer)->postJson(route('coop.pickups.preview'), [
            'date' => today()->addDay()->toDateString(),
            'requests' => [],
        ]);

        $response->assertForbidden();
    }
}
