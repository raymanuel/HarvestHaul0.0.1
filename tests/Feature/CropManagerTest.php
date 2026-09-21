<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\Crop;
use App\Models\CropAvailability;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\ReceivingRecord;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CropManagerTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'email_verified_at' => now()]);
    }

    public function test_admin_can_manage_category_lifecycle(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.crops.categories.store'), ['name' => 'Vegetables'])->assertRedirect();
        $category = CropCategory::firstWhere('name', 'Vegetables');
        $this->assertNotNull($category);

        $this->actingAs($admin)->put(route('admin.crops.categories.update', $category), [
            'name' => 'Vegetables & Herbs', 'status' => 'active',
        ])->assertRedirect();
        $this->assertEquals('Vegetables & Herbs', $category->fresh()->name);

        $this->actingAs($admin)->delete(route('admin.crops.categories.destroy', $category))->assertRedirect();
        $this->assertNull(CropCategory::find($category->id));
    }

    public function test_category_cannot_be_deleted_with_crops(): void
    {
        $admin = $this->superAdmin();
        $category = CropCategory::create(['name' => 'Fruits', 'status' => 'active']);
        Crop::create(['crop_category_id' => $category->id, 'name' => 'Banana', 'status' => 'active']);

        $response = $this->actingAs($admin)->delete(route('admin.crops.categories.destroy', $category));

        $response->assertRedirect();
        $this->assertNotNull(CropCategory::find($category->id));
    }

    public function test_admin_can_manage_crop_lifecycle(): void
    {
        $admin = $this->superAdmin();
        $category = CropCategory::create(['name' => 'Fruits', 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.crops.store'), [
            'crop_category_id' => $category->id, 'name' => 'Mango',
        ])->assertRedirect();
        $crop = Crop::firstWhere('name', 'Mango');
        $this->assertNotNull($crop);

        $this->actingAs($admin)->put(route('admin.crops.update', $crop), [
            'crop_category_id' => $category->id, 'name' => 'Mango (Carabao)', 'status' => 'active',
        ])->assertRedirect();
        $this->assertEquals('Mango (Carabao)', $crop->fresh()->name);

        $this->actingAs($admin)->delete(route('admin.crops.destroy', $crop))->assertRedirect();
        $this->assertNull(Crop::find($crop->id));
    }

    public function test_crop_cannot_be_deleted_with_varieties(): void
    {
        $admin = $this->superAdmin();
        $category = CropCategory::create(['name' => 'Fruits', 'status' => 'active']);
        $crop = Crop::create(['crop_category_id' => $category->id, 'name' => 'Banana', 'status' => 'active']);
        CropVariety::create(['crop_id' => $crop->id, 'name' => 'Lakatan', 'price_per_kg' => 30, 'status' => 'active']);

        $response = $this->actingAs($admin)->delete(route('admin.crops.destroy', $crop));

        $response->assertRedirect();
        $this->assertNotNull(Crop::find($crop->id));
    }

    public function test_admin_can_manage_variety_lifecycle(): void
    {
        $admin = $this->superAdmin();
        $category = CropCategory::create(['name' => 'Fruits', 'status' => 'active']);
        $crop = Crop::create(['crop_category_id' => $category->id, 'name' => 'Banana', 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.crops.varieties.store'), [
            'crop_id' => $crop->id, 'name' => 'Lakatan', 'price_per_kg' => 30,
        ])->assertRedirect();
        $variety = CropVariety::firstWhere('name', 'Lakatan');
        $this->assertNotNull($variety);

        $this->actingAs($admin)->put(route('admin.crops.varieties.update', $variety), [
            'name' => 'Lakatan Premium', 'price_per_kg' => 35, 'status' => 'active',
        ])->assertRedirect();
        $this->assertEquals('Lakatan Premium', $variety->fresh()->name);

        // Regression: destroyVariety() used to call a dead harvests() relation and throw on every call.
        $response = $this->actingAs($admin)->delete(route('admin.crops.varieties.destroy', $variety));
        $response->assertRedirect();
        $this->assertNull(CropVariety::find($variety->id));
    }

    public function test_variety_cannot_be_deleted_when_referenced_by_receiving_record(): void
    {
        $admin = $this->superAdmin();
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $category = CropCategory::create(['name' => 'Fruits', 'status' => 'active']);
        $crop = Crop::create(['crop_category_id' => $category->id, 'name' => 'Banana', 'status' => 'active']);
        $variety = CropVariety::create(['crop_id' => $crop->id, 'name' => 'Lakatan', 'price_per_kg' => 30, 'status' => 'active']);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $job = \App\Models\HaulJob::create(['cooperative_id' => $coop->id, 'job_type' => \App\Models\HaulJob::JOB_TYPE_PICKUP, 'pickup_date' => today(), 'status' => \App\Models\HaulJob::STATUS_SCHEDULED]);
        $grade = \App\Models\CropGrade::create(['name' => 'Class A', 'is_active' => true, 'sort_order' => 1]);

        ReceivingRecord::create([
            'haul_job_id' => $job->id, 'cooperative_id' => $coop->id, 'farmer_id' => $farmer->id,
            'crop_id' => $crop->id, 'crop_variety_id' => $variety->id, 'crop_grade_id' => $grade->id,
            'actual_weight_kg' => 10, 'status' => ReceivingRecord::STATUS_PENDING, 'recorded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.crops.varieties.destroy', $variety));

        $response->assertRedirect();
        $this->assertNotNull(CropVariety::find($variety->id));
    }

    public function test_variety_cannot_be_deleted_when_referenced_by_crop_availability(): void
    {
        $admin = $this->superAdmin();
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop2@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $category = CropCategory::create(['name' => 'Fruits', 'status' => 'active']);
        $crop = Crop::create(['crop_category_id' => $category->id, 'name' => 'Banana', 'status' => 'active']);
        $variety = CropVariety::create(['crop_id' => $crop->id, 'name' => 'Lakatan', 'price_per_kg' => 30, 'status' => 'active']);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $job = \App\Models\HaulJob::create(['cooperative_id' => $coop->id, 'job_type' => \App\Models\HaulJob::JOB_TYPE_PICKUP, 'pickup_date' => today(), 'status' => \App\Models\HaulJob::STATUS_SCHEDULED]);
        $grade = \App\Models\CropGrade::create(['name' => 'Class A', 'is_active' => true, 'sort_order' => 1]);
        $receiving = ReceivingRecord::create([
            'haul_job_id' => $job->id, 'cooperative_id' => $coop->id, 'farmer_id' => $farmer->id,
            'crop_id' => $crop->id, 'crop_variety_id' => $variety->id, 'crop_grade_id' => $grade->id,
            'actual_weight_kg' => 100, 'status' => ReceivingRecord::STATUS_CONFIRMED, 'recorded_by' => $admin->id,
        ]);

        CropAvailability::create([
            'cooperative_id' => $coop->id, 'crop_id' => $crop->id, 'crop_variety_id' => $variety->id,
            'receiving_record_id' => $receiving->id,
            'quantity_kg' => 100, 'status' => CropAvailability::STATUS_AVAILABLE,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.crops.varieties.destroy', $variety));

        $response->assertRedirect();
        $this->assertNotNull(CropVariety::find($variety->id));
    }

    public function test_duplicate_crop_name_under_same_category_rejected(): void
    {
        $admin = $this->superAdmin();
        $category = CropCategory::create(['name' => 'Fruits', 'status' => 'active']);
        Crop::create(['crop_category_id' => $category->id, 'name' => 'Banana', 'status' => 'active']);

        $response = $this->actingAs($admin)->post(route('admin.crops.store'), [
            'crop_category_id' => $category->id, 'name' => 'Banana',
        ]);

        $response->assertRedirect();
        $this->assertEquals(1, Crop::where('name', 'Banana')->count());
    }

    public function test_non_admin_cannot_manage_crops(): void
    {
        $coopAdmin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value]);

        $response = $this->actingAs($coopAdmin)->get(route('admin.crops.index'));

        $response->assertForbidden();
    }
}
