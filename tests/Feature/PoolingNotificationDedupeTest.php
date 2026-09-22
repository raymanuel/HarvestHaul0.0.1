<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\Negotiation;
use App\Models\Notification;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PoolingNotificationDedupeTest extends TestCase
{
    use RefreshDatabase;

    private function createLogisticsUser(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name' => 'Test Logistics',
            'business_permit_no' => 'BL-12345',
            'phone' => '09123456789',
            'is_verified' => true,
            'logistics_type' => 'company',
        ]);

        return $user;
    }

    private function createFarmerUser(): User
    {
        $user = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $user->farmerProfile()->create([
            'phone' => '09123456789',
            'farm_location' => 'Test Farm',
            'is_verified' => true,
            'latitude' => 7.0,
            'longitude' => 125.5,
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
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_name' => 'Test Truck',
            'plate_number' => 'ABC-1234',
            'capacity_kg' => 5000,
            'status' => 'available',
            'vehicle_type' => 'truck',
        ]);
    }

    private function createHarvestForFarmer(User $farmer, string $cropName = 'Rice', float $quantityKg = 100): Harvest
    {
        $category = CropCategory::firstOrCreate(['name' => 'Grains'], ['status' => 'active']);
        $crop = Crop::firstOrCreate(['name' => $cropName], ['crop_category_id' => $category->id, 'status' => 'active']);
        $variety = CropVariety::firstOrCreate(['crop_id' => $crop->id, 'name' => 'Standard'], ['status' => 'active']);

        return Harvest::create([
            'user_id' => $farmer->id,
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'crop_category_id' => $category->id,
            'crop_type' => $crop->name,
            'variety' => $variety->name,
            'quantity_kg' => $quantityKg,
            'remaining_quantity_kg' => $quantityKg,
            'unit' => 'kg',
            'status' => 'active',
            'destination_address' => 'Test',
            'destination_latitude' => 7.0,
            'destination_longitude' => 125.0,
        ]);
    }

    // ─────────────────────────────────────────────────
    // 1. CONFIRM NOTIFICATION DEDUPE
    // ─────────────────────────────────────────────────

    public function test_confirm_notifies_each_party_once(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmerF = $this->createFarmerUser();
        $farmerG = $this->createFarmerUser();
        $driver = $this->createDriverUser();

        $truck = $this->createTruck($logisticsUser);
        $truck->update(['assigned_driver_id' => $driver->id, 'status' => 'assigned']);

        $job = PoolingJob::create([
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
            'status' => PoolingJobStatus::PENDING,
            'total_kg' => 500,
            'truck_capacity_kg' => 5000,
            'farm_count' => 2,
        ]);

        $harvestF = $this->createHarvestForFarmer($farmerF, 'Rice');
        $harvestG = $this->createHarvestForFarmer($farmerG, 'Corn');

        $job->harvests()->attach($harvestF->id, [
            'pickup_order' => 1, 'quantity_kg' => 250,
            'status' => 'pending', 'cost_share' => 1250,
        ]);
        $job->harvests()->attach($harvestG->id, [
            'pickup_order' => 2, 'quantity_kg' => 250,
            'status' => 'pending', 'cost_share' => 1250,
        ]);

        Notification::query()->delete();

        // Accept as farmer F
        $this->actingAs($farmerF)->post(route('pooling.accept', $job->id))->assertRedirect();
        // Accept as farmer G (last) — triggers confirm
        $this->actingAs($farmerG)->post(route('pooling.accept', $job->id))->assertRedirect();

        $job->refresh();
        $this->assertEquals(PoolingJobStatus::CONFIRMED, $job->status);

        // Exactly 1 notification for driver (title is date-stamped: "Route Booked for …")
        $driverNotifs = Notification::where('user_id', $driver->id)->where('title', 'like', 'Route Booked for%')->count();
        $this->assertEquals(1, $driverNotifs, 'Driver should receive exactly 1 confirm notification');

        // Exactly 1 notification for logistics
        $logisticsNotifs = Notification::where('user_id', $logisticsUser->id)->where('title', 'Proposal Confirmed')->count();
        $this->assertEquals(1, $logisticsNotifs, 'Logistics should receive exactly 1 confirm notification');

        // Exactly 1 notification for each farmer
        $farmerFNotifs = Notification::where('user_id', $farmerF->id)->where('title', 'Route Confirmed')->count();
        $this->assertEquals(1, $farmerFNotifs, 'Farmer F should receive exactly 1 confirm notification');

        $farmerGNotifs = Notification::where('user_id', $farmerG->id)->where('title', 'Route Confirmed')->count();
        $this->assertEquals(1, $farmerGNotifs, 'Farmer G should receive exactly 1 confirm notification');
    }

    // ─────────────────────────────────────────────────
    // 2. EXPIRY FAIRNESS
    // ─────────────────────────────────────────────────

    public function test_expired_proposal_keeps_partial_sale_and_notifies_farmer(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmer = $this->createFarmerUser();

        $truck = $this->createTruck($logisticsUser);

        $job = PoolingJob::create([
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_id' => $truck->id,
            'status' => PoolingJobStatus::PENDING,
            'total_kg' => 100,
            'truck_capacity_kg' => 5000,
            'farm_count' => 1,
            'proposal_expires_at' => now()->subHours(49),
        ]);

        $harvest = $this->createHarvestForFarmer($farmer, 'Rice', 100.0);
        $harvest->update(['status' => HarvestStatus::ASSIGNED]);

        // Completed negotiation for 40 of 100 kg (partially sold)
        Negotiation::factory()->completed()->create([
            'harvest_id' => $harvest->id,
            'farmer_id' => $farmer->id,
            'negotiated_volume' => 40.0,
        ]);

        $job->harvests()->attach($harvest->id, [
            'pickup_order' => 1, 'quantity_kg' => 40,
            'status' => 'pending', 'cost_share' => 200,
        ]);

        Notification::query()->delete();

        // Run the auto-reject command
        $exitCode = Artisan::call('proposals:auto-reject-expired');
        $this->assertEquals(0, $exitCode);

        // Harvest should be partially_sold, NOT sold
        $harvest->refresh();
        $this->assertEquals(HarvestStatus::PARTIALLY_SOLD, $harvest->status,
            'Harvest with partial negotiation should be partially_sold, not sold');

        // Job should be cancelled
        $job->refresh();
        $this->assertEquals(PoolingJobStatus::CANCELLED, $job->status);

        // Farmer should receive a notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $farmer->id,
            'type' => 'proposal_expired',
        ]);
    }

    public function test_expired_proposal_full_negotiation_sets_sold(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmer = $this->createFarmerUser();

        $truck = $this->createTruck($logisticsUser);

        $job = PoolingJob::create([
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_id' => $truck->id,
            'status' => PoolingJobStatus::PENDING,
            'total_kg' => 100,
            'truck_capacity_kg' => 5000,
            'farm_count' => 1,
            'proposal_expires_at' => now()->subHours(49),
        ]);

        $harvest = $this->createHarvestForFarmer($farmer, 'Rice', 100.0);
        $harvest->update(['status' => HarvestStatus::ASSIGNED]);

        // Completed negotiation for ALL 100 kg
        Negotiation::factory()->completed()->create([
            'harvest_id' => $harvest->id,
            'farmer_id' => $farmer->id,
            'negotiated_volume' => 100.0,
        ]);

        $job->harvests()->attach($harvest->id, [
            'pickup_order' => 1, 'quantity_kg' => 100,
            'status' => 'pending', 'cost_share' => 500,
        ]);

        Artisan::call('proposals:auto-reject-expired');

        $harvest->refresh();
        $this->assertEquals(HarvestStatus::SOLD, $harvest->status,
            'Fully negotiated harvest should be sold');
    }

    public function test_expired_proposal_no_negotiation_sets_active(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmer = $this->createFarmerUser();

        $truck = $this->createTruck($logisticsUser);

        $job = PoolingJob::create([
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_id' => $truck->id,
            'status' => PoolingJobStatus::PENDING,
            'total_kg' => 100,
            'truck_capacity_kg' => 5000,
            'farm_count' => 1,
            'proposal_expires_at' => now()->subHours(49),
        ]);

        $harvest = $this->createHarvestForFarmer($farmer, 'Rice', 100.0);
        $harvest->update(['status' => HarvestStatus::ASSIGNED]);
        // No completed negotiation

        $job->harvests()->attach($harvest->id, [
            'pickup_order' => 1, 'quantity_kg' => 100,
            'status' => 'pending', 'cost_share' => 500,
        ]);

        Artisan::call('proposals:auto-reject-expired');

        $harvest->refresh();
        $this->assertEquals(HarvestStatus::ACTIVE, $harvest->status,
            'No completed negotiation → harvest returns to active');
    }

    public function test_expired_proposal_sets_pivot_cancelled(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmer = $this->createFarmerUser();

        $truck = $this->createTruck($logisticsUser);

        $job = PoolingJob::create([
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_id' => $truck->id,
            'status' => PoolingJobStatus::PENDING,
            'total_kg' => 100,
            'truck_capacity_kg' => 5000,
            'farm_count' => 1,
            'proposal_expires_at' => now()->subHours(49),
        ]);

        $harvest = $this->createHarvestForFarmer($farmer, 'Rice', 100.0);
        $harvest->update(['status' => HarvestStatus::ASSIGNED]);

        $job->harvests()->attach($harvest->id, [
            'pickup_order' => 1, 'quantity_kg' => 100,
            'status' => 'pending', 'cost_share' => 500,
        ]);

        Artisan::call('proposals:auto-reject-expired');

        $job->refresh();
        $pivot = $job->harvests()->where('harvest_id', $harvest->id)->first()->pivot;
        $this->assertContains($pivot->status, ['cancelled', 'expired'],
            'Pivot should be in a terminal state after expiry');
    }

    // ─────────────────────────────────────────────────
    // 3. AUTO-COMPLETE PIVOT TIDINESS
    // ─────────────────────────────────────────────────

    public function test_auto_complete_sets_terminal_pivot_status(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmer = $this->createFarmerUser();

        $truck = $this->createTruck($logisticsUser);

        $job = PoolingJob::create([
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_id' => $truck->id,
            'status' => PoolingJobStatus::IN_PROGRESS,
            'total_kg' => 100,
            'truck_capacity_kg' => 5000,
            'farm_count' => 1,
        ]);

        $harvest = $this->createHarvestForFarmer($farmer, 'Rice', 100.0);
        $harvest->update(['status' => HarvestStatus::IN_PROGRESS]);

        $job->harvests()->attach($harvest->id, [
            'pickup_order' => 1, 'quantity_kg' => 100,
            'status' => 'accepted', 'cost_share' => 500,
        ]);

        // Force updated_at to 49 hours ago so the stale check finds it
        DB::table('pooling_jobs')
            ->where('id', $job->id)
            ->update(['updated_at' => now()->subHours(49)]);

        // Force updated_at to 49 hours ago so the stale check finds it
        DB::table('pooling_jobs')
            ->where('id', $job->id)
            ->update(['updated_at' => now()->subHours(49)]);

        $exitCode = Artisan::call('deliveries:auto-complete-stale');
        $this->assertEquals(0, $exitCode);

        $job->refresh();
        $this->assertEquals(PoolingJobStatus::COMPLETED, $job->status);

        $pivot = $job->harvests()->where('harvest_id', $harvest->id)->first()->pivot;
        $this->assertEquals('delivered', $pivot->status,
            'Pivot should be set to delivered after auto-complete');
        $this->assertNotNull($pivot->delivered_at,
            'delivered_at should be set on pivot after auto-complete');
    }
}
