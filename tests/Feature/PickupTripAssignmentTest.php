<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\HaulRequest;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'status'                => HaulRequest::STATUS_PENDING,
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
            'status'                => HaulRequest::STATUS_PENDING,
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
}
