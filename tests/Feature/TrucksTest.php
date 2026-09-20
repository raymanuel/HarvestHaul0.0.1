<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Cooperative;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrucksTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cooperative admin whose cooperative is approved, wired via the
     * cooperative_id column (see EnsureUserIsApprovedCoopAdmin middleware).
     */
    private function coop(): User
    {
        $cooperative = Cooperative::create([
            'name'                     => 'GenSan AgCoop',
            'type'                     => 'primary',
            'province'                 => 'South Cotabato',
            'city'                     => 'General Santos',
            'municipality'             => 'General Santos',
            'barangay'                 => 'SanIsidro',
            'contact_number'           => '09171234567',
            'official_email'           => 'coop@example.com',
            'status'                   => Cooperative::STATUS_APPROVED,
        ]);

        return User::factory()->create([
            'role'              => UserRole::COOP_ADMIN->value,
            'cooperative_id'    => $cooperative->id,
            'email_verified_at' => now(),
        ]);
    }

    public function test_coop_sees_its_own_fleet_with_totals(): void
    {
        $coop = $this->coop();

        Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'cooperative_id'          => $coop->cooperative_id,
            'truck_name'              => 'Hitachi 12',
            'plate_number'            => 'MEK-101',
            'vehicle_type'            => 'wing van',
            'capacity_kg'             => 8000,
            'capacity_volume_cubic_m' => 50,
            'status'                  => 'available',
            'notes'                   => 'Primary reefer for corn runs.',
        ]);
        Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'cooperative_id'          => $coop->cooperative_id,
            'truck_name'              => 'Foton 6',
            'plate_number'            => 'MEK-202',
            'vehicle_type'            => 'forward truck',
            'capacity_kg'             => 4000,
            'capacity_volume_cubic_m' => 30,
            'status'                  => 'in_use',
        ]);

        $response = $this->actingAs($coop)->get(route('coop.trucks.index'));
        $response->assertOk();
        $response->assertSee('Hitachi 12');
        $response->assertSee('Foton 6');
    }

    public function test_coop_can_add_a_truck_to_the_fleet(): void
    {
        $coop = $this->coop();

        $response = $this->actingAs($coop)->post(route('coop.trucks.store'), [
            'truck_name'              => 'Hino 300',
            'plate_number'            => 'MEK-303',
            'vehicle_type'            => 'forward truck',
            'capacity_kg'             => 6000,
            'capacity_volume_cubic_m' => 40,
            'status'                  => 'available',
            'notes'                   => 'Ready for weekend haul.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Truck added to the fleet.');

        $this->assertDatabaseHas('trucks', [
            'cooperative_id' => $coop->cooperative_id,
            'plate_number'   => 'MEK-303',
            'truck_name'     => 'Hino 300',
            'status'         => 'available',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'admin_id'    => $coop->id,
            'action'      => 'create_truck',
            'target_type' => 'truck',
        ]);
    }

    public function test_truck_status_can_be_toggled_with_audit(): void
    {
        $coop = $this->coop();

        $truck = Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'cooperative_id'          => $coop->cooperative_id,
            'truck_name'              => 'Isuzu 4',
            'plate_number'            => 'MEK-404',
            'vehicle_type'            => 'closed van',
            'capacity_kg'             => 4000,
            'capacity_volume_cubic_m' => 25,
            'status'                  => 'available',
        ]);

        $response = $this->actingAs($coop)->post(
            route('coop.trucks.status', [$truck, 'in_use'])
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('trucks', [
            'id'     => $truck->id,
            'status' => 'in_use',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'toggle_truck_status',
            'target_type' => 'truck',
            'target_id'   => $truck->id,
        ]);
    }

    public function test_truck_can_be_removed_from_the_fleet(): void
    {
        $coop = $this->coop();

        $truck = Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'cooperative_id'          => $coop->cooperative_id,
            'truck_name'              => 'Mitsubishi Fuso',
            'plate_number'            => 'MEK-505',
            'vehicle_type'            => 'wing van',
            'capacity_kg'             => 9000,
            'capacity_volume_cubic_m' => 55,
            'status'                  => 'available',
        ]);

        $response = $this->actingAs($coop)->delete(route('coop.trucks.destroy', $truck));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('trucks', [
            'id' => $truck->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'delete_truck',
            'target_type' => 'truck',
        ]);
    }

    public function test_coop_cannot_assign_another_cooperatives_driver_to_a_truck(): void
    {
        $coopA = $this->coop();
        $coopB = $this->coop();

        $driverFromCoopB = User::factory()->create([
            'role'           => UserRole::DELIVERY_PERSONNEL->value,
            'cooperative_id' => $coopB->cooperative_id,
        ]);

        $response = $this->actingAs($coopA)->post(route('coop.trucks.store'), [
            'truck_name'              => 'Isuzu 5',
            'plate_number'            => 'MEK-505',
            'vehicle_type'            => 'closed van',
            'capacity_kg'             => 4000,
            'capacity_volume_cubic_m' => 25,
            'status'                  => 'available',
            'driver_id'               => $driverFromCoopB->id,
        ]);

        $response->assertSessionHasErrors('driver_id');
        $this->assertDatabaseMissing('trucks', ['plate_number' => 'MEK-505']);
    }

    public function test_coop_cannot_assign_non_driver_user_to_a_truck(): void
    {
        $coop = $this->coop();

        $fieldPersonnel = User::factory()->create([
            'role'           => UserRole::FIELD_RECEIVING->value,
            'cooperative_id' => $coop->cooperative_id,
        ]);

        $response = $this->actingAs($coop)->post(route('coop.trucks.store'), [
            'truck_name'              => 'Isuzu 6',
            'plate_number'            => 'MEK-606',
            'vehicle_type'            => 'closed van',
            'capacity_kg'             => 4000,
            'capacity_volume_cubic_m' => 25,
            'status'                  => 'available',
            'driver_id'               => $fieldPersonnel->id,
        ]);

        $response->assertSessionHasErrors('driver_id');
        $this->assertDatabaseMissing('trucks', ['plate_number' => 'MEK-606']);
    }
}
