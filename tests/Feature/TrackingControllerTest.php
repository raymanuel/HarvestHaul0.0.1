<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\FarmerProfile;
use App\Models\PoolingJob;
use App\Models\Negotiation;

class TrackingControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeDealScene(User $buyer, User $otherBuyer, string $negotiationStatus): PoolingJob
    {
        $farmer = User::factory()->farmer()->create();

        $category  = \App\Models\CropCategory::firstOrCreate(['name' => 'Grains'], ['status' => 'active']);
        $crop      = \App\Models\Crop::factory()->create(['crop_category_id' => $category->id]);
        $variety   = \App\Models\CropVariety::firstOrCreate(['crop_id' => $crop->id, 'name' => 'IR64'], ['status' => 'active']);

        $harvest = $farmer->harvests()->create([
            'user_id'              => $farmer->id,
            'crop_id'              => $crop->id,
            'crop_category_id'     => $category->id,
            'crop_variety_id'      => $variety->id,
            'crop_type'            => $crop->name,
            'variety'              => $variety->name,
            'quantity_kg'          => 1000,
            'unit'                 => 'kg',
            'status'               => 'sold',
            'latitude'             => 7.5,
            'longitude'            => 125.0,
            'destination_address'  => 'Test Market',
            'destination_latitude' => 7.2,
            'destination_longitude'=> 124.8,
        ]);

        Negotiation::create([
            'buyer_id'       => $buyer->id,
            'farmer_id'      => $farmer->id,
            'harvest_id'     => $harvest->id,
            'negotiated_volume' => 1000,
            'status'         => $negotiationStatus,
        ]);

        $logistics = User::factory()->logisticsPartner()->create();
        $logistics->logisticsProfile()->create([
            'company_name'       => 'Coop Logistics',
            'business_permit_no' => 'BL-12345',
            'phone'              => '09123456789',
            'is_verified'        => true,
            'logistics_type'     => 'cooperative',
        ]);

        $truck = \App\Models\Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'truck_name'           => 'Test Truck',
            'plate_number'         => 'ABC-1234',
            'capacity_kg'          => 5000,
            'status'               => 'available',
            'vehicle_type'         => 'truck',
        ]);

        $driver = User::factory()->driver()->create();

        $job = PoolingJob::factory()->confirmed()->create([
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'truck_id'             => $truck->id,
            'driver_id'            => $driver->id,
        ]);
        $job->harvests()->attach($harvest->id, ['quantity_kg' => 1000, 'pickup_order' => 0]);

        return $job;
    }

    public function test_buyer_sees_multi_buyer_job_via_completed_negotiation(): void
    {
        $buyer = User::factory()->buyer()->create();
        $job = $this->makeDealScene($buyer, User::factory()->buyer()->create(), 'COMPLETED');

        $response = $this->actingAs($buyer)->get(route('tracking.index'));

        $response->assertOk();
        $response->assertViewHas('activeJobs', function ($jobs) use ($job) {
            return $jobs->contains('id', $job->id);
        });
    }

    public function test_unrelated_buyer_does_not_see_job_without_completed_negotiation(): void
    {
        $buyer          = User::factory()->buyer()->create();
        $unrelatedBuyer = User::factory()->buyer()->create();
        $this->makeDealScene($buyer, $unrelatedBuyer, 'COMPLETED');

        $response = $this->actingAs($unrelatedBuyer)->get(route('tracking.index'));

        $response->assertOk();
        $response->assertViewHas('activeJobs', function ($jobs) {
            return $jobs->isEmpty();
        });
    }
}