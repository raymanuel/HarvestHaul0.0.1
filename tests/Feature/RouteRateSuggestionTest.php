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

class RouteRateSuggestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['harvesthaul.hauling' => [
            'base_rate_per_km'        => 15.00,
            'base_rate_per_kg'        => 0.50,
            'base_trip_fee'           => 250.00,
            'fuel_liters_per_km'      => 0.135,
            'fuel_price_per_liter'    => 58.00,
            'maintenance_cost_per_km' => 3.50,
            'driver_cost_per_km'      => 4.00,
            'terrain_multipliers'     => ['flat' => 1.00, 'rolling' => 1.15, 'mountainous' => 1.35],
            'suggestion_sources'      => [],
        ]]);
    }

    private function createLogisticsUser(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name'       => 'Test Logistics',
            'business_permit_no' => 'BL-99999',
            'phone'              => '09123456789',
            'is_verified'        => true,
            'logistics_type'     => 'company',
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
            'affiliation_type' => 'independent',
        ]);
        return $user;
    }

    private function createHarvest(User $farmer, int $qty): Harvest
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
            'status'                => 'partially_sold',
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
            'plate_number'         => 'DEF-5678',
            'capacity_kg'          => 5000,
            'status'               => 'available',
            'vehicle_type'         => 'truck',
        ]);
    }

    private function makePlan(User $logisticsUser, array $overrides = []): array
    {
        $farmerA = $this->createFarmer();
        $farmerB = $this->createFarmer();
        $harvestA = $this->createHarvest($farmerA, 300);
        $harvestB = $this->createHarvest($farmerB, 200);

        $rateA = $overrides['perFarmerRateA'] ?? null;
        unset($overrides['perFarmerRateA']);

        Negotiation::factory()->create([
            'harvest_id'         => $harvestA->id,
            'buyer_id'           => $logisticsUser->id,
            'farmer_id'          => $farmerA->id,
            'status'             => NegotiationStatus::COMPLETED,
            'negotiated_volume'  => 300,
            'hauling_rate_per_kg' => $rateA,
        ]);
        Negotiation::factory()->create([
            'harvest_id'         => $harvestB->id,
            'buyer_id'           => $logisticsUser->id,
            'farmer_id'          => $farmerB->id,
            'status'             => NegotiationStatus::COMPLETED,
            'negotiated_volume'  => 200,
            'hauling_rate_per_kg' => null,
        ]);

        $truck = $this->createTruck($logisticsUser);

        $defaults = [
            'truck'             => $truck,
            'nearbyHarvestIds'  => [$harvestA->id, $harvestB->id],
            'startLat'          => 7.0,
            'startLng'          => 125.5,
            'endLat'            => 8.0,
            'endLng'            => 126.0,
            'radiusKm'          => 300.0,
            'haulingRatePerKg'  => 1.50,
            'farmDistances'     => [],
            'routeDistanceKm'   => 100.0,
            'terrain'           => 'mountainous',
        ];

        return app(ResourcePoolingService::class)->plan(...array_merge($defaults, $overrides));
    }

    public function test_plan_includes_road_distance_suggestion_and_sanity(): void
    {
        $logistics = $this->createLogisticsUser();
        $plan = $this->makePlan($logistics);

        $this->assertTrue($plan['success']);
        $this->assertEquals(100.0, (float) $plan['road_distance_km']);
        $this->assertEquals('mountainous', $plan['terrain']);

        // costPerKm flat ≈ 15.33 × 1.35 mountainous ≈ 20.70; trip = 20.70×100 + 250 ≈ 2319.55
        $this->assertNotNull($plan['suggested_rate_per_kg']);
        $this->assertEqualsWithDelta(4.64, (float) $plan['suggested_rate_per_kg'], 0.01);

        // Quoted 1.50 vs suggested ~4.64 → way below → warning (amber) banner.
        $this->assertEquals('warning', $plan['rate_sanity']['level']);
        $this->assertStringContainsString('loss', $plan['rate_sanity']['message']);
        $this->assertNotEmpty($plan['rate_source']);

        // Typed rate is still the source of truth for the price reference.
        $this->assertEqualsWithDelta(750.00, (float) $plan['price_reference'], 0.01);
    }

    public function test_fallback_reference_price_prefers_road_distance(): void
    {
        $logistics = $this->createLogisticsUser();

        // No flat rate + road distance provided → fallback uses base_rate_per_km (15) × road km (200).
        $plan = $this->makePlan($logistics, ['haulingRatePerKg' => 0, 'routeDistanceKm' => 200.0, 'terrain' => 'flat']);

        $expected = (200.0 * 15.00) + (500.0 * 0.50) + 250.0; // 3000 + 250 + 250 = 3500
        $this->assertTrue($plan['success']);
        $this->assertEqualsWithDelta($expected, (float) $plan['price_reference'], 0.01);
        $this->assertEquals(200.0, (float) $plan['road_distance_km']);
    }

    public function test_confirm_persists_route_characteristics(): void
    {
        $logistics = $this->createLogisticsUser();
        $plan = $this->makePlan($logistics);

        $job = app(ResourcePoolingService::class)->confirm($plan, $logistics->logisticsProfile->id);

        $this->assertInstanceOf(PoolingJob::class, $job);
        $this->assertEquals(100.0, (float) $job->road_distance_km);
        $this->assertEquals('mountainous', $job->terrain);
        $this->assertNotEmpty($job->rate_source);
    }

    public function test_coop_sanity_uses_flat_fallback_for_farmers_without_agreed_rate(): void
    {
        $logistics = $this->createLogisticsUser();

        // Farmer A agreed ₱2.00/kg; Farmer B has no agreed rate so the flat
        // ₱8.00/kg fallback applies (matches the cost allocation logic).
        // trip = 15.33×1.35×100 + 250 ≈ 2319.5 → suggested ≈ ₱4.64/kg.
        $plan = $this->makePlan($logistics, ['perFarmerRateA' => 2.00, 'haulingRatePerKg' => 8.00]);

        // Effective rate = (2.00×300 + 8.00×200)/500 = 4.40 → ≈ 95% of the
        // suggestion, a healthy rate → no warning banner.
        $this->assertTrue($plan['success']);
        $this->assertNull($plan['rate_sanity'], 'Effective rate must include the flat fallback for Farmer B.');
    }

    public function test_plan_includes_clickable_source_links(): void
    {
        config(['harvesthaul.hauling.suggestion_sources' => [
            ['label' => 'DOE Oil Price Watch', 'source' => 'DOE', 'url' => 'https://www.doe.gov.ph/oil-price-watch-1'],
            ['label' => 'ICCT truck economy', 'source' => 'ICCT', 'url' => 'https://theicct.org/'],
        ]]);

        $logistics = $this->createLogisticsUser();
        $plan = $this->makePlan($logistics);

        $this->assertNotEmpty($plan['suggestion_sources']);
        $this->assertEquals('DOE Oil Price Watch', $plan['suggestion_sources'][0]['label']);
        $this->assertEquals('https://www.doe.gov.ph/oil-price-watch-1', $plan['suggestion_sources'][0]['url']);
    }
}