<?php

namespace Tests\Feature;

use App\Models\PoolingJob;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoolingJobPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function createLogisticsUser(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name'       => 'Test Logistics',
            'business_permit_no' => 'BL-12345',
            'phone'              => '09123456789',
            'is_verified'        => true,
            'logistics_type'     => 'company',
        ]);
        return $user;
    }

    private function createFarmerUser(): User
    {
        $user = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $user->farmerProfile()->create([
            'phone'            => '09123456789',
            'farm_location'    => 'Test Farm',
            'is_verified'      => true,
            'latitude'         => 7.0,
            'longitude'        => 125.5,
            'affiliation_type' => 'independent',
        ]);
        return $user;
    }

    private function createDriverUser(): User
    {
        return User::factory()->driver()->create(['email_verified_at' => now()]);
    }

    private function createTruck(User $logisticsUser): Truck
    {
        return Truck::create([
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_name'           => 'Test Truck',
            'plate_number'         => 'ABC-1234',
            'capacity_kg'          => 5000,
            'status'               => 'available',
            'vehicle_type'         => 'truck',
        ]);
    }

    private function createJob(User $logisticsUser, array $overrides = []): PoolingJob
    {
        $truck = $this->createTruck($logisticsUser);

        return PoolingJob::create(array_merge([
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_id'             => $truck->id,
            'status'               => 'pending',
            'total_kg'             => 500,
            'truck_capacity_kg'    => 5000,
            'farm_count'           => 1,
        ], $overrides));
    }

    private function createHarvestForFarmer(User $farmer): \App\Models\Harvest
    {
        $category = \App\Models\CropCategory::create(['name' => 'Grains', 'status' => 'active']);
        $crop = \App\Models\Crop::create(['crop_category_id' => $category->id, 'name' => 'Rice', 'status' => 'active']);
        $variety = \App\Models\CropVariety::create(['crop_id' => $crop->id, 'name' => 'IR64', 'status' => 'active']);

        return \App\Models\Harvest::create([
            'user_id'               => $farmer->id,
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'crop_category_id'      => $category->id,
            'crop_type'             => $crop->name,
            'variety'               => $variety->name,
            'quantity_kg'           => 100,
            'remaining_quantity_kg' => 100,
            'unit'                  => 'kg',
            'status'                => 'assigned',
            'destination_address'   => 'Test',
            'destination_latitude'  => 7.0,
            'destination_longitude' => 125.0,
        ]);
    }

    // ─── VIEW AUTHORIZATION ──────────────────────────────────

    public function test_logistics_owner_can_view_job(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $job = $this->createJob($logisticsUser);
        $job->load('harvests');

        $this->assertTrue($logisticsUser->can('view', $job));
    }

    public function test_farmer_with_harvest_in_job_can_view(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmer = $this->createFarmerUser();
        $harvest = $this->createHarvestForFarmer($farmer);
        $job = $this->createJob($logisticsUser);

        $job->harvests()->attach($harvest->id, [
            'pickup_order' => 1,
            'quantity_kg'  => 100,
            'status'       => 'pending',
        ]);

        $job->load('harvests');

        $this->assertTrue($farmer->can('view', $job));
    }

    public function test_buyer_on_job_can_view(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $buyer = User::factory()->buyer()->create(['email_verified_at' => now()]);
        $job = $this->createJob($logisticsUser, ['buyer_id' => $buyer->id]);
        $job->load('harvests');

        $this->assertTrue($buyer->can('view', $job));
    }

    public function test_driver_on_job_can_view(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $driver = $this->createDriverUser();
        $job = $this->createJob($logisticsUser, ['driver_id' => $driver->id]);
        $job->load('harvests');

        $this->assertTrue($driver->can('view', $job));
    }

    public function test_unrelated_user_cannot_view_job(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $outsider = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $job = $this->createJob($logisticsUser);
        $job->load('harvests');

        $this->assertFalse($outsider->can('view', $job));
    }

    public function test_admin_can_view_any_job(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $admin = User::factory()->admin()->create(['email_verified_at' => now()]);
        $job = $this->createJob($logisticsUser);
        $job->load('harvests');

        $this->assertTrue($admin->can('view', $job));
    }

    // ─── UPDATE AUTHORIZATION ────────────────────────────────

    public function test_logistics_owner_can_update_job(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $job = $this->createJob($logisticsUser);
        $job->load('harvests');

        $this->assertTrue($logisticsUser->can('update', $job));
    }

    public function test_farmer_cannot_update_job(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmer = $this->createFarmerUser();
        $job = $this->createJob($logisticsUser);
        $job->load('harvests');

        $this->assertFalse($farmer->can('update', $job));
    }

    public function test_buyer_cannot_update_job(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $buyer = User::factory()->buyer()->create(['email_verified_at' => now()]);
        $job = $this->createJob($logisticsUser, ['buyer_id' => $buyer->id]);
        $job->load('harvests');

        $this->assertFalse($buyer->can('update', $job));
    }

    public function test_driver_cannot_update_job(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $driver = $this->createDriverUser();
        $job = $this->createJob($logisticsUser, ['driver_id' => $driver->id]);
        $job->load('harvests');

        $this->assertFalse($driver->can('update', $job));
    }

    // ─── MANAGE HARVESTS AUTHORIZATION ───────────────────────

    public function test_logistics_owner_can_manage_harvests(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $job = $this->createJob($logisticsUser);
        $job->load('harvests');

        $this->assertTrue($logisticsUser->can('manageHarvests', $job));
    }

    public function test_driver_can_manage_harvests(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $driver = $this->createDriverUser();
        $job = $this->createJob($logisticsUser, ['driver_id' => $driver->id, 'status' => 'in_progress']);
        $job->load('harvests');

        $this->assertTrue($driver->can('manageHarvests', $job));
    }

    public function test_farmer_cannot_manage_harvests(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmer = $this->createFarmerUser();
        $job = $this->createJob($logisticsUser);
        $job->load('harvests');

        $this->assertFalse($farmer->can('manageHarvests', $job));
    }
}
