<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\Harvest;
use App\Models\Negotiation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerOrderMatchAndReportsTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedFarmer(string $name): User
    {
        $user = User::factory()->farmer()->create(['name' => $name]);
        $user->farmerProfile()->create([
            'farm_location' => 'Test Farm',
            'is_verified' => true,
            'affiliation_type' => 'independent',
            'latitude' => '7.00000000',
            'longitude' => '125.00000000',
        ]);

        return $user;
    }

    private function activeHarvest(User $farmer, array $overrides = []): Harvest
    {
        return Harvest::factory()->create(array_merge([
            'user_id' => $farmer->id,
            'status' => 'active',
            'visibility' => 'both',
            'quantity_kg' => 100,
            'remaining_quantity_kg' => 100,
            'latitude' => '7.00000000',
            'longitude' => '125.00000000',
            'destination_latitude' => '7.00000000',
            'destination_longitude' => '125.00000000',
        ], $overrides));
    }

    private function crop(array $overrides = []): Crop
    {
        return Crop::factory()->create(array_merge([
            'crop_category_id' => CropCategory::factory(),
        ], $overrides));
    }

    private function buyer(): User
    {
        return User::factory()->buyer()->create(['name' => 'Test Buyer']);
    }

    public function test_crop_board_defaults_to_best_match_ranking(): void
    {
        $crop = $this->crop(['baseline_price_per_kg' => 100]);
        $variety = CropVariety::create([
            'crop_id' => $crop->id, 'name' => 'Test Variety', 'status' => 'active', 'price_per_kg' => 100,
        ]);

        $cheapFarmer = $this->verifiedFarmer('Farmer Cheap Post');
        $pricyFarmer = $this->verifiedFarmer('Farmer Pricy Post');

        $this->activeHarvest($cheapFarmer, ['crop_id' => $crop->id, 'crop_variety_id' => $variety->id, 'suggested_price_per_kg' => 80]);
        $this->activeHarvest($pricyFarmer, ['crop_id' => $crop->id, 'crop_variety_id' => $variety->id, 'suggested_price_per_kg' => 140]);

        $response = $this->actingAs($this->buyer())->get(route('buyer.crop-board'));

        $response->assertOk();
        $response->assertSeeInOrder(['Farmer Cheap Post', 'Farmer Pricy Post']);
    }

    public function test_crop_board_lowest_price_sort_reorders_posts(): void
    {
        $crop = $this->crop(['baseline_price_per_kg' => 100]);
        $variety = CropVariety::create([
            'crop_id' => $crop->id, 'name' => 'Test Variety', 'status' => 'active', 'price_per_kg' => 100,
        ]);

        $midFarmer = $this->verifiedFarmer('Farmer Mid Price');
        $highFarmer = $this->verifiedFarmer('Farmer High Price');
        $lowFarmer = $this->verifiedFarmer('Farmer Low Price');

        $this->activeHarvest($midFarmer, ['crop_id' => $crop->id, 'crop_variety_id' => $variety->id, 'suggested_price_per_kg' => 80]);
        $this->activeHarvest($highFarmer, ['crop_id' => $crop->id, 'crop_variety_id' => $variety->id, 'suggested_price_per_kg' => 200]);
        $this->activeHarvest($lowFarmer, ['crop_id' => $crop->id, 'crop_variety_id' => $variety->id, 'suggested_price_per_kg' => 50]);

        $response = $this->actingAs($this->buyer())->get(route('buyer.crop-board', ['sort' => 'lowest_price']));

        $response->assertOk();
        $response->assertSeeInOrder(['Farmer Low Price', 'Farmer Mid Price', 'Farmer High Price']);
    }

    public function test_buyer_reports_page_renders_completed_purchases(): void
    {
        $buyer = $this->buyer();
        $farmer = $this->verifiedFarmer('Supplier Farmer');
        $crop = $this->crop(['name' => 'Test Crop']);
        $harvest = $this->activeHarvest($farmer, ['crop_id' => $crop->id]);

        Negotiation::factory()->completed()->create([
            'buyer_id' => $buyer->id,
            'farmer_id' => $farmer->id,
            'harvest_id' => $harvest->id,
            'negotiated_price' => 90,
            'negotiated_volume' => 50,
        ]);

        $response = $this->actingAs($buyer)->get(route('buyer.reports'));

        $response->assertOk();
        $response->assertSee('Total Spent');
        $response->assertSee('Test Crop');
    }

    public function test_buyer_reports_csv_download(): void
    {
        $buyer = $this->buyer();
        $farmer = $this->verifiedFarmer('Supplier Farmer');
        $crop = $this->crop(['name' => 'Test Crop']);
        $harvest = $this->activeHarvest($farmer, ['crop_id' => $crop->id]);

        Negotiation::factory()->completed()->create([
            'buyer_id' => $buyer->id,
            'farmer_id' => $farmer->id,
            'harvest_id' => $harvest->id,
            'negotiated_price' => 90,
            'negotiated_volume' => 50,
        ]);

        $response = $this->actingAs($buyer)->get(route('buyer.reports.download'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
