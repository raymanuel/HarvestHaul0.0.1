<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\Harvest;
use App\Models\Negotiation;
use App\Models\NegotiationStatus;
use App\Models\PoolingJob;
use App\Models\Truck;
use App\Models\User;
use App\Services\ResourcePoolingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiBuyerPoolingTest extends TestCase
{
    use RefreshDatabase;

    private function createLogisticsUser(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name'       => 'Coop Logistics',
            'business_permit_no' => 'BL-12345',
            'phone'              => '09123456789',
            'is_verified'        => true,
            'logistics_type'     => 'cooperative',
        ]);
        return $user;
    }

    private function createFarmer(): User
    {
        $user = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $user->farmerProfile()->create([
            'phone'            => '09123456789',
            'farm_location'    => 'Test Farm',
            'is_verified'      => true,
            'latitude'         => 7.0,
            'longitude'        => 125.5,
            'affiliation_type' => 'cooperative',
        ]);
        return $user;
    }

    private function createSoldHarvest(User $farmer, int $qty): Harvest
    {
        $category = CropCategory::firstOrCreate(['name' => 'Grains'], ['status' => 'active']);
        $crop = Crop::firstOrCreate(['name' => 'Rice'], ['crop_category_id' => $category->id, 'status' => 'active']);
        $variety = CropVariety::firstOrCreate(['crop_id' => $crop->id, 'name' => 'IR64'], ['status' => 'active']);

        return Harvest::create([
            'user_id'               => $farmer->id,
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'crop_category_id'      => $category->id,
            'crop_type'             => $crop->name,
            'variety'               => $variety->name,
            'quantity_kg'           => $qty,
            'remaining_quantity_kg' => $qty,
            'unit'                  => 'kg',
            'status'                => 'sold',
            'destination_address'   => 'Test Market',
            'destination_latitude'  => 8.0,
            'destination_longitude' => 126.0,
            'latitude'              => 7.0,
            'longitude'             => 125.5,
        ]);
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

    public function test_route_can_pool_deals_from_different_buyers(): void
    {
        $logistics = $this->createLogisticsUser();
        $buyerA    = User::factory()->buyer()->create(['email_verified_at' => now()]);
        $buyerB    = User::factory()->buyer()->create(['email_verified_at' => now()]);
        $farmerA   = $this->createFarmer();
        $farmerB   = $this->createFarmer();

        $harvestA = $this->createSoldHarvest($farmerA, 300);
        $harvestB = $this->createSoldHarvest($farmerB, 200);

        Negotiation::factory()->create([
            'harvest_id'          => $harvestA->id,
            'buyer_id'            => $buyerA->id,
            'farmer_id'           => $farmerA->id,
            'status'              => NegotiationStatus::COMPLETED,
            'negotiated_volume'   => 300,
            'hauling_rate_per_kg' => 5.00,
        ]);
        Negotiation::factory()->create([
            'harvest_id'          => $harvestB->id,
            'buyer_id'            => $buyerB->id,
            'farmer_id'           => $farmerB->id,
            'status'              => NegotiationStatus::COMPLETED,
            'negotiated_volume'   => 200,
            'hauling_rate_per_kg' => 8.00,
        ]);

        $truck = $this->createTruck($logistics);

        $service = app(ResourcePoolingService::class);
        $plan = $service->plan(
            truck:             $truck,
            nearbyHarvestIds:  [$harvestA->id, $harvestB->id],
            startLat:          7.0,
            startLng:          125.5,
            endLat:            8.0,
            endLng:            126.0,
            radiusKm:          100,
        );

        $this->assertTrue($plan['success'], 'Multi-buyer deals must be poolable into one route');
        $this->assertCount(2, $plan['stops'], 'Both buyers\' deals should be on the route');
        $this->assertEquals(2, $plan['requested_count']);
        $this->assertEquals(2, $plan['selected_count']);
    }

    public function test_confirm_sets_buyer_id_null_and_both_buyers_can_view(): void
    {
        $logistics = $this->createLogisticsUser();
        $buyerA    = User::factory()->buyer()->create(['email_verified_at' => now()]);
        $buyerB    = User::factory()->buyer()->create(['email_verified_at' => now()]);
        $farmerA   = $this->createFarmer();
        $farmerB   = $this->createFarmer();

        $harvestA = $this->createSoldHarvest($farmerA, 300);
        $harvestB = $this->createSoldHarvest($farmerB, 200);

        Negotiation::factory()->create([
            'harvest_id'          => $harvestA->id,
            'buyer_id'            => $buyerA->id,
            'farmer_id'           => $farmerA->id,
            'status'              => NegotiationStatus::COMPLETED,
            'negotiated_volume'   => 300,
            'hauling_rate_per_kg' => 5.00,
        ]);
        Negotiation::factory()->create([
            'harvest_id'          => $harvestB->id,
            'buyer_id'            => $buyerB->id,
            'farmer_id'           => $farmerB->id,
            'status'              => NegotiationStatus::COMPLETED,
            'negotiated_volume'   => 200,
            'hauling_rate_per_kg' => 8.00,
        ]);

        $truck = $this->createTruck($logistics);

        $service = app(ResourcePoolingService::class);
        $plan = $service->plan(
            truck:            $truck,
            nearbyHarvestIds: [$harvestA->id, $harvestB->id],
            startLat:         7.0,
            startLng:         125.5,
            endLat:           8.0,
            endLng:           126.0,
            radiusKm:         100,
        );
        $this->assertTrue($plan['success']);

        $job = $service->confirm($plan, $logistics->logisticsProfile->id);

        $this->assertNull($job->buyer_id, 'Multi-buyer job must not store a single buyer');
        $this->assertEquals(2, (int) $job->farm_count);

        $job->load('harvests');

        $this->assertTrue($buyerA->can('view', $job), 'Buyer A with a completed deal should view the job');
        $this->assertTrue($buyerB->can('view', $job), 'Buyer B with a completed deal should view the job');
    }
}