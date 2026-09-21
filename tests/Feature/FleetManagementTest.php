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

class FleetManagementTest extends TestCase
{
    use RefreshDatabase;

    private function fakeOsrm(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok',
                'durations' => array_fill(0, 5, array_fill(0, 5, 300)),
                'distances' => array_fill(0, 5, array_fill(0, 5, 5000)),
            ]),
            'router.project-osrm.org/route/*' => Http::response([
                'code' => 'Ok',
                'routes' => [['distance' => 10000, 'duration' => 900, 'geometry' => ['coordinates' => [[125.1716, 6.1164], [125.18, 6.12]]]]],
            ]),
        ]);
    }

    private function cooperative(): Cooperative
    {
        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED, 'latitude' => '6.1164', 'longitude' => '125.1716',
        ]);
    }

    private function coopAdmin(Cooperative $cooperative): User
    {
        return User::factory()->create([
            'role' => UserRole::COOP_ADMIN->value,
            'cooperative_id' => $cooperative->id,
            'email_verified_at' => now(),
        ]);
    }

    public function test_store_rejects_an_in_use_truck(): void
    {
        $this->fakeOsrm();
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        $date = today()->addDay()->toDateString();

        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'in_use', 'capacity_kg' => 5000]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $req = HaulRequest::factory()->create([
            'cooperative_id' => $coop->id, 'status' => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date, 'pickup_location_lat' => 6.12, 'pickup_location_lng' => 125.18,
        ]);

        $response = $this->actingAs($coopAdmin)->post(route('coop.pickups.store'), [
            'date' => $date,
            'truck_id' => $truck->id,
            'delivery_personnel_id' => $driver->id,
            'requests' => [$req->id],
        ]);

        $response->assertSessionHasErrors('truck_id');
    }

    public function test_reassign_rejects_a_truck_already_in_use_by_another_trip(): void
    {
        $this->fakeOsrm();
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $ownTruck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'in_use']);
        $busyTruck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'in_use']);

        $job = HaulJob::create([
            'haul_request_id' => null, 'cooperative_id' => $coop->id,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $ownTruck->id,
            'pickup_date' => today()->addDay(), 'status' => HaulJob::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($coopAdmin)->put(route('coop.pickups.reassign', $job), [
            'truck_id' => $busyTruck->id,
            'delivery_personnel_id' => $driver->id,
        ]);

        $response->assertSessionHasErrors('truck_id');
    }

    public function test_reassign_accepts_the_trips_own_current_truck_unchanged(): void
    {
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $ownTruck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'in_use']);

        $job = HaulJob::create([
            'haul_request_id' => null, 'cooperative_id' => $coop->id,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $ownTruck->id,
            'pickup_date' => today()->addDay(), 'status' => HaulJob::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($coopAdmin)->put(route('coop.pickups.reassign', $job), [
            'truck_id' => $ownTruck->id,
            'delivery_personnel_id' => $driver->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('trucks', ['id' => $ownTruck->id, 'status' => 'in_use']);
    }

    public function test_reassign_to_a_different_truck_frees_old_and_claims_new(): void
    {
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $oldTruck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'in_use']);
        $newTruck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'available']);

        $job = HaulJob::create([
            'haul_request_id' => null, 'cooperative_id' => $coop->id,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $oldTruck->id,
            'pickup_date' => today()->addDay(), 'status' => HaulJob::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($coopAdmin)->put(route('coop.pickups.reassign', $job), [
            'truck_id' => $newTruck->id,
            'delivery_personnel_id' => $driver->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('trucks', ['id' => $oldTruck->id, 'status' => 'available']);
        $this->assertDatabaseHas('trucks', ['id' => $newTruck->id, 'status' => 'in_use']);
    }

    public function test_toggle_status_accepts_inactive_and_excludes_it_from_planning(): void
    {
        $this->fakeOsrm();
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'available']);

        $response = $this->actingAs($coopAdmin)->post(route('coop.trucks.status', [$truck, 'inactive']));
        $response->assertRedirect();
        $this->assertDatabaseHas('trucks', ['id' => $truck->id, 'status' => 'inactive']);

        $date = today()->addDay()->toDateString();
        HaulRequest::factory()->create([
            'cooperative_id' => $coop->id, 'status' => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date, 'pickup_location_lat' => 6.12, 'pickup_location_lng' => 125.18,
        ]);

        $plan = app(ConsolidationEngine::class)->planForDate($coop, $date);

        $this->assertFalse($plan['trucks']->contains('id', $truck->id));
    }

    public function test_destroy_blocked_while_truck_has_active_trip(): void
    {
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'in_use']);

        HaulJob::create([
            'haul_request_id' => null, 'cooperative_id' => $coop->id,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $truck->id,
            'pickup_date' => today()->addDay(), 'status' => HaulJob::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($coopAdmin)->delete(route('coop.trucks.destroy', $truck));

        $response->assertSessionHasErrors('truck');
        $this->assertDatabaseHas('trucks', ['id' => $truck->id]);
    }

    public function test_destroy_allowed_once_trip_is_terminal(): void
    {
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'available']);

        HaulJob::create([
            'haul_request_id' => null, 'cooperative_id' => $coop->id,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $truck->id,
            'pickup_date' => today()->subDay(), 'status' => HaulJob::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($coopAdmin)->delete(route('coop.trucks.destroy', $truck));

        $response->assertRedirect();
        $this->assertDatabaseMissing('trucks', ['id' => $truck->id]);
    }
}
