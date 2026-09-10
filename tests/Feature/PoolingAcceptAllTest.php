<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\Harvest;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoolingAcceptAllTest extends TestCase
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

    private function createJob(User $logisticsUser): PoolingJob
    {
        $truck = $this->createTruck($logisticsUser);

        return PoolingJob::create([
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_id'             => $truck->id,
            'status'               => PoolingJobStatus::PENDING,
            'total_kg'             => 500,
            'truck_capacity_kg'    => 5000,
            'farm_count'           => 1,
        ]);
    }

    private function createHarvestForFarmer(User $farmer, string $cropName = 'Rice', float $quantityKg = 100): Harvest
    {
        $category = CropCategory::firstOrCreate(['name' => 'Grains'], ['status' => 'active']);
        $crop = Crop::firstOrCreate(['name' => $cropName], ['crop_category_id' => $category->id, 'status' => 'active']);
        $variety = CropVariety::firstOrCreate(['crop_id' => $crop->id, 'name' => 'Standard'], ['status' => 'active']);

        return Harvest::create([
            'user_id'               => $farmer->id,
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'crop_category_id'      => $category->id,
            'crop_type'             => $crop->name,
            'variety'               => $variety->name,
            'quantity_kg'           => $quantityKg,
            'remaining_quantity_kg' => $quantityKg,
            'unit'                  => 'kg',
            'status'                => 'active',
            'destination_address'   => 'Test',
            'destination_latitude'  => 7.0,
            'destination_longitude' => 125.0,
        ]);
    }

    public function test_farmer_accepts_all_their_harvests_at_once(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmerF = $this->createFarmerUser();
        $farmerG = $this->createFarmerUser();

        $job = $this->createJob($logisticsUser);

        // F owns 2 harvests, G owns 1
        $harvestF1 = $this->createHarvestForFarmer($farmerF, 'Rice');
        $harvestF2 = $this->createHarvestForFarmer($farmerF, 'Corn');
        $harvestG = $this->createHarvestForFarmer($farmerG, 'Banana');

        $job->harvests()->attach($harvestF1->id, [
            'pickup_order' => 1,
            'quantity_kg'  => 100,
            'status'       => 'pending',
            'cost_share'   => 500,
        ]);
        $job->harvests()->attach($harvestF2->id, [
            'pickup_order' => 2,
            'quantity_kg'  => 200,
            'status'       => 'pending',
            'cost_share'   => 800,
        ]);
        $job->harvests()->attach($harvestG->id, [
            'pickup_order' => 3,
            'quantity_kg'  => 150,
            'status'       => 'pending',
            'cost_share'   => 600,
        ]);

        $job->load('harvests');

        // Accept as farmer F
        $response = $this->actingAs($farmerF)
            ->post(route('pooling.accept', $job->id));

        $response->assertRedirect();

        // Reload and assert BOTH F's pivots are accepted
        $job->load('harvests');
        $fHarvests = $job->harvests->where('user_id', $farmerF->id);
        $this->assertCount(2, $fHarvests);
        foreach ($fHarvests as $h) {
            $this->assertEquals('accepted', $h->pivot->status, "Harvest #{$h->id} should be accepted");
        }

        // G's pivot is still pending
        $gPivot = $job->harvests->firstWhere('user_id', $farmerG->id)->pivot;
        $this->assertEquals('pending', $gPivot->status);

        // Job still pending (G hasn't accepted yet)
        $job->refresh();
        $this->assertEquals(PoolingJobStatus::PENDING, $job->status);
    }

    public function test_accept_all_confirms_when_last_farmer_accepts(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmerF = $this->createFarmerUser();
        $farmerG = $this->createFarmerUser();

        $job = $this->createJob($logisticsUser);

        $harvestF1 = $this->createHarvestForFarmer($farmerF, 'Rice');
        $harvestF2 = $this->createHarvestForFarmer($farmerF, 'Corn');
        $harvestG = $this->createHarvestForFarmer($farmerG, 'Banana');

        $job->harvests()->attach($harvestF1->id, [
            'pickup_order' => 1,
            'quantity_kg'  => 100,
            'status'       => 'pending',
            'cost_share'   => 500,
        ]);
        $job->harvests()->attach($harvestF2->id, [
            'pickup_order' => 2,
            'quantity_kg'  => 200,
            'status'       => 'pending',
            'cost_share'   => 800,
        ]);
        $job->harvests()->attach($harvestG->id, [
            'pickup_order' => 3,
            'quantity_kg'  => 150,
            'status'       => 'pending',
            'cost_share'   => 600,
        ]);

        // F accepts
        $this->actingAs($farmerF)
            ->post(route('pooling.accept', $job->id))
            ->assertRedirect();

        $job->refresh();
        $this->assertEquals(PoolingJobStatus::PENDING, $job->status);

        // G accepts (last farmer) → job confirms
        $this->actingAs($farmerG)
            ->post(route('pooling.accept', $job->id))
            ->assertRedirect();

        $job->refresh();
        $this->assertEquals(PoolingJobStatus::CONFIRMED, $job->status);

        // A notification was created for logistics user
        $this->assertDatabaseHas('notifications', [
            'user_id' => $logisticsUser->id,
            'title'   => 'Proposal Confirmed',
        ]);
    }

    public function test_farmer_with_no_crops_on_offer_gets_graceful_error(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmerF = $this->createFarmerUser();
        $outsider = $this->createFarmerUser();

        $job = $this->createJob($logisticsUser);

        $harvestF = $this->createHarvestForFarmer($farmerF, 'Rice');

        $job->harvests()->attach($harvestF->id, [
            'pickup_order' => 1,
            'quantity_kg'  => 100,
            'status'       => 'pending',
            'cost_share'   => 500,
        ]);

        $job->load('harvests');

        // Outsider with no harvests on this job tries to accept
        $response = $this->actingAs($outsider)
            ->post(route('pooling.accept', $job->id));

        $response->assertStatus(403);

        // Job unchanged
        $job->refresh();
        $this->assertEquals(PoolingJobStatus::PENDING, $job->status);

        // Harvests untouched
        $job->load('harvests');
        $this->assertEquals('pending', $job->harvests->first()->pivot->status);
    }
}
