<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\MarketPrice;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketPriceTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'email_verified_at' => now()]);
    }

    public function test_admin_can_record_market_price(): void
    {
        $admin = $this->superAdmin();
        $category = CropCategory::create(['name' => 'Fruit & Veg', 'status' => 'active']);
        $crop = Crop::create(['crop_category_id' => $category->id, 'name' => 'Banana', 'status' => 'active']);

        $response = $this->actingAs($admin)->post(route('admin.market-prices.store'), [
            'crop_id' => $crop->id,
            'low_price_per_kg' => 25,
            'high_price_per_kg' => 35,
            'common_price_per_kg' => 32,
            'dpi_price_per_kg' => 30,
            'price_date' => now()->toDateString(),
            'source' => 'DA RFO12',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('market_prices', ['crop_id' => $crop->id, 'common_price_per_kg' => 32]);
    }

    public function test_non_admin_cannot_record_market_price(): void
    {
        $coopAdmin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value]);
        $category = CropCategory::create(['name' => 'Fruit & Veg', 'status' => 'active']);
        $crop = Crop::create(['crop_category_id' => $category->id, 'name' => 'Banana', 'status' => 'active']);

        $response = $this->actingAs($coopAdmin)->post(route('admin.market-prices.store'), [
            'crop_id' => $crop->id,
            'low_price_per_kg' => 25,
            'high_price_per_kg' => 35,
            'common_price_per_kg' => 32,
            'price_date' => now()->toDateString(),
        ]);

        $response->assertForbidden();
    }

    public function test_latest_for_each_crop_returns_newest_price_date(): void
    {
        $category = CropCategory::create(['name' => 'Fruit & Veg', 'status' => 'active']);
        $crop = Crop::create(['crop_category_id' => $category->id, 'name' => 'Banana', 'status' => 'active']);

        MarketPrice::create(['crop_id' => $crop->id, 'low_price_per_kg' => 20, 'high_price_per_kg' => 28, 'common_price_per_kg' => 24, 'price_date' => now()->subDays(3)]);
        MarketPrice::create(['crop_id' => $crop->id, 'low_price_per_kg' => 25, 'high_price_per_kg' => 35, 'common_price_per_kg' => 32, 'price_date' => now()]);

        $latest = MarketPrice::latestForEachCrop();

        $this->assertCount(1, $latest);
        $this->assertEquals(32, (float) $latest->first()->common_price_per_kg);
    }

    public function test_latest_for_falls_back_to_crop_level_when_no_variety_price(): void
    {
        $category = CropCategory::create(['name' => 'Fruit & Veg', 'status' => 'active']);
        $crop = Crop::create(['crop_category_id' => $category->id, 'name' => 'Banana', 'status' => 'active']);
        $variety = CropVariety::create(['crop_id' => $crop->id, 'name' => 'Lakatan', 'status' => 'active']);

        MarketPrice::create(['crop_id' => $crop->id, 'crop_variety_id' => null, 'low_price_per_kg' => 20, 'high_price_per_kg' => 28, 'common_price_per_kg' => 24, 'price_date' => now()]);

        $found = MarketPrice::latestFor($crop->id, $variety->id);

        $this->assertNotNull($found);
        $this->assertEquals(24, (float) $found->common_price_per_kg);
    }

    public function test_admin_can_delete_market_price(): void
    {
        $admin = $this->superAdmin();
        $category = CropCategory::create(['name' => 'Fruit & Veg', 'status' => 'active']);
        $crop = Crop::create(['crop_category_id' => $category->id, 'name' => 'Banana', 'status' => 'active']);
        $price = MarketPrice::create(['crop_id' => $crop->id, 'low_price_per_kg' => 20, 'high_price_per_kg' => 28, 'common_price_per_kg' => 24, 'price_date' => now()]);

        $response = $this->actingAs($admin)->delete(route('admin.market-prices.destroy', $price));

        $response->assertRedirect();
        $this->assertDatabaseMissing('market_prices', ['id' => $price->id]);
    }

    public function test_welcome_page_renders_with_and_without_data(): void
    {
        $this->get(route('welcome'))->assertOk();

        $category = CropCategory::create(['name' => 'Fruit & Veg', 'status' => 'active']);
        $crop = Crop::create(['crop_category_id' => $category->id, 'name' => 'Banana', 'status' => 'active']);
        MarketPrice::create(['crop_id' => $crop->id, 'low_price_per_kg' => 20, 'high_price_per_kg' => 28, 'common_price_per_kg' => 24, 'price_date' => now()]);

        $this->get(route('welcome'))->assertOk()->assertSee('Banana');
    }

    public function test_admin_market_prices_index_renders(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get(route('admin.market-prices.index'))->assertOk();
    }
}
