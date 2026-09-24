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

class PickupTripAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function coopAdmin(): User
    {
        $cooperative = Cooperative::create([
            'name'           => 'GenSan AgCoop',
            'type'           => 'primary',
            'province'       => 'South Cotabato',
            'city'           => 'General Santos',
            'contact_number' => '09171234567',
            'official_email' => 'coop@example.com',
            'status'         => Cooperative::STATUS_APPROVED,
        ]);

        return User::factory()->create([
            'role'              => UserRole::COOP_ADMIN->value,
            'cooperative_id'    => $cooperative->id,
            'email_verified_at' => now(),
        ]);
    }

    public function test_coop_cannot_assign_another_cooperatives_delivery_personnel_to_a_trip(): void
    {
        $coopA = $this->coopAdmin();
        $coopB = $this->coopAdmin();

        $truck = Truck::factory()->create(['cooperative_id' => $coopA->cooperative_id]);
        $driverFromCoopB = User::factory()->create([
            'role'           => UserRole::DELIVERY_PERSONNEL->value,
            'cooperative_id' => $coopB->cooperative_id,
        ]);

        $haulRequest = HaulRequest::factory()->create([
            'cooperative_id'        => $coopA->cooperative_id,
            'status'                => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => today()->addDay(),
        ]);

        $response = $this->actingAs($coopA)->post(route('coop.pickups.store'), [
            'date'                   => today()->addDay()->toDateString(),
            'truck_id'               => $truck->id,
            'delivery_personnel_id'  => $driverFromCoopB->id,
            'requests'               => [$haulRequest->id],
        ]);

        $response->assertSessionHasErrors('delivery_personnel_id');
        $this->assertDatabaseMissing('haul_jobs', ['cooperative_id' => $coopA->cooperative_id]);
    }

    public function test_coop_cannot_assign_another_cooperatives_truck_to_a_trip(): void
    {
        $coopA = $this->coopAdmin();
        $coopB = $this->coopAdmin();

        $truckFromCoopB = Truck::factory()->create(['cooperative_id' => $coopB->cooperative_id]);
        $driver = User::factory()->create([
            'role'           => UserRole::DELIVERY_PERSONNEL->value,
            'cooperative_id' => $coopA->cooperative_id,
        ]);

        $haulRequest = HaulRequest::factory()->create([
            'cooperative_id'        => $coopA->cooperative_id,
            'status'                => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => today()->addDay(),
        ]);

        $response = $this->actingAs($coopA)->post(route('coop.pickups.store'), [
            'date'                   => today()->addDay()->toDateString(),
            'truck_id'               => $truckFromCoopB->id,
            'delivery_personnel_id'  => $driver->id,
            'requests'               => [$haulRequest->id],
        ]);

        $response->assertSessionHasErrors('truck_id');
    }

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
                'routes' => [[
                    'distance' => 10000,
                    'duration' => 900,
                    'geometry' => ['coordinates' => [[125.1716, 6.1164], [125.18, 6.12], [125.1716, 6.1164]]],
                ]],
            ]),
        ]);
    }

    public function test_reassign_truck_options_exclude_busy_trucks_except_the_incumbent(): void
    {
        $this->fakeOsrm();
        $coop = $this->coopAdmin();
        $incumbentTruck = Truck::factory()->create(['cooperative_id' => $coop->cooperative_id, 'status' => 'in_use']);
        $busyElsewhereTruck = Truck::factory()->create(['cooperative_id' => $coop->cooperative_id, 'status' => 'in_use']);
        $freeTruck = Truck::factory()->create(['cooperative_id' => $coop->cooperative_id, 'status' => 'available']);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->cooperative_id]);

        $job = HaulJob::create([
            'haul_request_id' => null, 'cooperative_id' => $coop->cooperative_id,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $incumbentTruck->id,
            'pickup_date' => today()->addDay(), 'status' => HaulJob::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($coop)->get(route('coop.pickups.show', $job));

        $response->assertOk();
        $trucks = $response->viewData('trucks');
        $this->assertTrue($trucks->contains('id', $incumbentTruck->id));
        $this->assertTrue($trucks->contains('id', $freeTruck->id));
        $this->assertFalse($trucks->contains('id', $busyElsewhereTruck->id));
    }

    public function test_field_staff_can_be_optionally_assigned_and_locks_them_from_other_trips(): void
    {
        $this->fakeOsrm();
        $coop = $this->coopAdmin();
        $fieldStaff = User::factory()->create(['role' => UserRole::FIELD_RECEIVING->value, 'cooperative_id' => $coop->cooperative_id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->cooperative_id, 'status' => 'available']);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->cooperative_id]);
        $date = today()->addDay()->toDateString();
        $req = HaulRequest::factory()->create([
            'cooperative_id' => $coop->cooperative_id, 'status' => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date, 'pickup_location_lat' => 6.12, 'pickup_location_lng' => 125.18,
        ]);

        $response = $this->actingAs($coop)->post(route('coop.pickups.store'), [
            'date' => $date, 'truck_id' => $truck->id, 'delivery_personnel_id' => $driver->id,
            'field_personnel_id' => $fieldStaff->id, 'requests' => [$req->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('haul_jobs', ['cooperative_id' => $coop->cooperative_id, 'field_personnel_id' => $fieldStaff->id]);

        $available = app(ConsolidationEngine::class)->availableFieldPersonnel($coop->cooperative);
        $this->assertFalse($available->contains('id', $fieldStaff->id));
    }

    public function test_field_staff_left_unassigned_still_succeeds(): void
    {
        $this->fakeOsrm();
        $coop = $this->coopAdmin();
        $truck = Truck::factory()->create(['cooperative_id' => $coop->cooperative_id, 'status' => 'available']);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->cooperative_id]);
        $date = today()->addDay()->toDateString();
        $req = HaulRequest::factory()->create([
            'cooperative_id' => $coop->cooperative_id, 'status' => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date, 'pickup_location_lat' => 6.12, 'pickup_location_lng' => 125.18,
        ]);

        $response = $this->actingAs($coop)->post(route('coop.pickups.store'), [
            'date' => $date, 'truck_id' => $truck->id, 'delivery_personnel_id' => $driver->id,
            'requests' => [$req->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('haul_jobs', ['cooperative_id' => $coop->cooperative_id, 'field_personnel_id' => null]);
    }

    public function test_field_staff_becomes_available_again_once_their_trip_completes(): void
    {
        $coop = $this->coopAdmin();
        $fieldStaff = User::factory()->create(['role' => UserRole::FIELD_RECEIVING->value, 'cooperative_id' => $coop->cooperative_id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->cooperative_id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->cooperative_id, 'status' => 'available']);

        HaulJob::create([
            'haul_request_id' => null, 'cooperative_id' => $coop->cooperative_id,
            'delivery_personnel_id' => $driver->id, 'field_personnel_id' => $fieldStaff->id,
            'truck_id' => $truck->id, 'pickup_date' => today(), 'status' => HaulJob::STATUS_COMPLETED,
        ]);

        $available = app(ConsolidationEngine::class)->availableFieldPersonnel($coop->cooperative);

        $this->assertTrue($available->contains('id', $fieldStaff->id));
    }
}
