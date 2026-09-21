<?php

namespace Tests\Feature;

use App\Models\CropGrade;
use App\Models\HaulJob;
use App\Models\HaulRequest;
use App\Models\PackagingType;
use App\Models\ReceivingRecord;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceDataTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'email_verified_at' => now()]);
    }

    public function test_store_grade_persists_description(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->post(route('admin.reference.grades.store'), [
            'name' => 'Premium',
            'code' => 'A',
            'description' => 'Uniform size, less than 5% moisture damage.',
            'sort_order' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('crop_grades', [
            'name' => 'Premium',
            'description' => 'Uniform size, less than 5% moisture damage.',
        ]);
    }

    public function test_update_grade_changes_fields_and_toggles_active(): void
    {
        $admin = $this->superAdmin();
        $grade = CropGrade::factory()->create(['name' => 'Old Name', 'is_active' => true]);

        $response = $this->actingAs($admin)->put(route('admin.reference.grades.update', $grade), [
            'name' => 'New Name',
            'sort_order' => 5,
            // is_active omitted — simulates an unchecked checkbox.
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('crop_grades', [
            'id' => $grade->id,
            'name' => 'New Name',
            'sort_order' => 5,
            'is_active' => false,
        ]);
    }

    public function test_update_grade_keeps_active_when_checkbox_checked(): void
    {
        $admin = $this->superAdmin();
        $grade = CropGrade::factory()->create(['is_active' => false]);

        $this->actingAs($admin)->put(route('admin.reference.grades.update', $grade), [
            'name' => $grade->name,
            'is_active' => '1',
        ]);

        $this->assertDatabaseHas('crop_grades', ['id' => $grade->id, 'is_active' => true]);
    }

    public function test_update_packaging_changes_fields(): void
    {
        $admin = $this->superAdmin();
        $packaging = PackagingType::factory()->create(['name' => 'Sack']);

        $response = $this->actingAs($admin)->put(route('admin.reference.packaging.update', $packaging), [
            'name' => 'Crate',
            'sort_order' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('packaging_types', ['id' => $packaging->id, 'name' => 'Crate', 'is_active' => false]);
    }

    public function test_deactivated_grade_excluded_from_active_scope_but_history_kept(): void
    {
        $admin = $this->superAdmin();
        $grade = CropGrade::factory()->create(['is_active' => true]);

        // Simulate an existing receiving record referencing this grade.
        $cooperativeId = \App\Models\Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => \App\Models\Cooperative::STATUS_APPROVED,
        ])->id;
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $cooperativeId]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $cooperativeId]);
        $truck = Truck::factory()->create(['cooperative_id' => $cooperativeId]);
        $req = HaulRequest::factory()->create(['cooperative_id' => $cooperativeId, 'status' => HaulRequest::STATUS_COMPLETED]);
        $job = HaulJob::create([
            'haul_request_id' => null, 'cooperative_id' => $cooperativeId,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $truck->id,
            'pickup_date' => today(), 'status' => HaulJob::STATUS_COMPLETED,
        ]);
        ReceivingRecord::create([
            'haul_job_id' => $job->id, 'haul_request_id' => $req->id, 'cooperative_id' => $cooperativeId,
            'farmer_id' => $farmer->id, 'crop_id' => $req->crop_id, 'crop_grade_id' => $grade->id,
            'actual_sacks' => 10, 'actual_weight_kg' => 500, 'recorded_by' => $driver->id,
            'status' => ReceivingRecord::STATUS_PENDING,
        ]);

        $this->actingAs($admin)->delete(route('admin.reference.grades.destroy', $grade));

        $this->assertFalse(CropGrade::active()->get()->contains('id', $grade->id));
        $this->assertDatabaseHas('receiving_records', ['crop_grade_id' => $grade->id]);
    }
}
