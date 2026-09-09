<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\Destination;
use App\Models\Harvest;
use App\Models\HaulIntent;
use App\Models\HaulRequest;
use App\Models\LogisticsProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HaulNegotiationRoomTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedIndependentFarmer(): User
    {
        $user = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $user->farmerProfile()->create([
            'phone'             => '09123456789',
            'farm_location'     => 'Test Farm',
            'is_verified'       => true,
            'latitude'          => 7.0,
            'longitude'         => 125.5,
            'affiliation_type'  => 'independent',
        ]);
        return $user;
    }

    private function createIndependentLogistics(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        LogisticsProfile::create([
            'user_id'          => $user->id,
            'company_name'     => 'Test Hauler',
            'phone'            => '09129876543',
            'is_verified'      => true,
            'logistics_type'   => 'company',
        ]);
        return $user;
    }

    private function createMarketDestination(): Destination
    {
        return Destination::create([
            'name'      => 'Davao Market',
            'address'   => 'Davao City',
            'latitude'  => 7.07,
            'longitude' => 125.61,
            'type'      => 'market',
            'is_active' => true,
        ]);
    }

    private function createActiveHarvest(User $farmer, ?Destination $destination = null): Harvest
    {
        $category = CropCategory::create(['name' => 'Grains', 'status' => 'active']);
        $crop = Crop::create(['crop_category_id' => $category->id, 'name' => 'Rice', 'status' => 'active']);
        $variety = CropVariety::create(['crop_id' => $crop->id, 'name' => 'IR64', 'status' => 'active']);

        return Harvest::create([
            'user_id'               => $farmer->id,
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'crop_category_id'      => $category->id,
            'crop_type'             => $crop->name,
            'variety'               => $variety->name,
            'quantity_kg'           => 500,
            'remaining_quantity_kg' => 500,
            'unit'                  => 'kg',
            'status'                => 'active',
            'visibility'            => 'both',
            'latitude'              => 7.1,
            'longitude'             => 125.5,
            'destination_id'        => $destination?->id,
            'destination_address'   => $destination?->name ?? 'Davao Market',
        ]);
    }

    private function createOpenIntent(User $farmer, User $logistics, Harvest $harvest): HaulIntent
    {
        $haulRequest = HaulRequest::create([
            'harvest_id' => $harvest->id,
            'user_id'    => $farmer->id,
            'status'     => 'open',
        ]);

        return HaulIntent::create([
            'haul_request_id'      => $haulRequest->id,
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'status'               => 'open',
        ]);
    }

    public function test_haul_room_provides_fair_rate_reference(): void
    {
        $farmer = $this->createVerifiedIndependentFarmer();
        $logistics = $this->createIndependentLogistics();
        $destination = $this->createMarketDestination();
        $harvest = $this->createActiveHarvest($farmer, $destination);
        $intent = $this->createOpenIntent($farmer, $logistics, $harvest);

        $this->actingAs($farmer)->get("/haul-negotiations/{$intent->id}")
            ->assertOk()
            ->assertViewHas('rateReference', fn ($ref) => is_float($ref) && $ref > 0);
    }
}