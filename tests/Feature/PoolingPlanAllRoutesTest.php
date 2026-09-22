<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\DriverProfile;
use App\Models\Harvest;
use App\Models\LogisticsProfile;
use App\Models\Negotiation;
use App\Models\PoolingJob;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PoolingPlanAllRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_all_route_registered_with_throttle(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('pooling.planAll');
        $this->assertNotNull($route);
        $this->assertEquals(['POST'], $route->methods());
        $this->assertStringContainsString('throttle:30,10', implode('|', $route->gatherMiddleware()));
    }

    public function test_confirm_batch_route_registered_with_throttle(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('pooling.confirmBatch');
        $this->assertNotNull($route);
        $this->assertEquals(['POST'], $route->methods());
        $this->assertStringContainsString('throttle:10,10', implode('|', $route->gatherMiddleware()));
    }

    public function test_unauthenticated_plan_all_redirects_to_login(): void
    {
        $this->post('/pooling/plan-all')->assertRedirect('/login');
    }

    public function test_unauthenticated_confirm_batch_redirects_to_login(): void
    {
        $this->post('/pooling/confirm-batch')->assertRedirect('/login');
    }

    public function test_confirm_batch_persists_stop_order_route_distance_and_farm_distances(): void
    {
        [$logistics, $fp] = $this->makeLogisticsUser();
        $truckA = $this->makeTruck($fp, 5000);

        $farmer1 = User::factory()->farmer()->create();
        $farmer2 = User::factory()->farmer()->create();

        $h1 = $this->makeHarvest($farmer1, 100);
        $h2 = $this->makeHarvest($farmer2, 100);
        $this->completeNegotiation($logistics, $farmer1, $h1, 100);
        $this->completeNegotiation($logistics, $farmer2, $h2, 100);

        $response = $this->actingAs($logistics)->postJson('/pooling/confirm-batch', [
            'plans' => [[
                'truck_id'            => $truckA->id,
                'harvest_ids'         => [$h1->id, $h2->id],
                'total_kg'            => 200,
                'start_lat'           => 7.0,
                'start_lng'           => 125.0,
                'end_lat'             => 7.1,
                'end_lng'             => 125.1,
                'radius_km'           => 50,
                'route_geometry'      => ['type' => 'LineString', 'coordinates' => [[125.0, 7.0], [125.1, 7.1]]],
                'stop_order'          => [$h2->id, $h1->id],
                'route_distance_km'   => 15.5,
                'farm_distances'      => [(string) $h1->id => 5.2, (string) $h2->id => 10.3],
                'hauling_rate_per_kg' => 2.00,
                'notes'               => 'Batch fix test',
            ]],
        ]);

        $response->assertOk();
        $this->assertCount(1, $response->json('job_ids'));

        $job = PoolingJob::firstOrFail();

        $this->assertEqualsWithDelta(15.5, (float) $job->road_distance_km, 0.001);
        $this->assertSame([$h1->id => 5.2, $h2->id => 10.3], $job->farm_distances);

        $orderH1 = (int) $job->harvests()->where('harvest_id', $h1->id)->first()->pivot->pickup_order;
        $orderH2 = (int) $job->harvests()->where('harvest_id', $h2->id)->first()->pivot->pickup_order;

        $this->assertLessThan($orderH1, $orderH2, 'Reversed stop order must be honored in the pivot pickup_order');
    }

    public function test_confirm_batch_rolls_back_whole_batch_when_any_plan_fails(): void
    {
        [$logistics, $fp] = $this->makeLogisticsUser();
        $truckA = $this->makeTruck($fp, 5000);
        $truckB = $this->makeTruck($fp, 5000);

        $farmers = [
            User::factory()->farmer()->create(),
            User::factory()->farmer()->create(),
            User::factory()->farmer()->create(),
            User::factory()->farmer()->create(),
        ];

        $harvests = [];
        foreach ($farmers as $i => $farmer) {
            $harvests[] = $this->makeHarvest($farmer, 100);
            $this->completeNegotiation($logistics, $farmer, $harvests[$i], 100);
        }
        [$h1, $h2, $h3, $h4] = $harvests;

        $basePlan = [
            'total_kg'            => 200,
            'start_lat'           => 7.0,
            'start_lng'           => 125.0,
            'end_lat'             => 7.1,
            'end_lng'             => 125.1,
            'radius_km'           => 50,
            'route_geometry'      => ['type' => 'LineString', 'coordinates' => [[125.0, 7.0], [125.1, 7.1]]],
            'stop_order'          => [],
            'hauling_rate_per_kg' => 2.00,
        ];

        $validPlan = array_merge($basePlan, [
            'truck_id'    => $truckA->id,
            'harvest_ids' => [$h1->id, $h2->id],
            'stop_order'  => [$h2->id, $h1->id],
        ]);

        $invalidPlan = array_merge($basePlan, [
            'truck_id'    => $truckB->id,
            'harvest_ids' => [$h3->id, $h4->id],
            'stop_order'  => [$h4->id, $h3->id],
            'total_kg'    => 999,
        ]);

        $response = $this->actingAs($logistics)->postJson('/pooling/confirm-batch', [
            'plans' => [$validPlan, $invalidPlan],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Route for truck #' . $truckB->id, $response->json('error'));

        $this->assertCount(0, PoolingJob::all(), 'A mid-batch failure must roll back earlier commits in the batch');

        $this->assertSame('available', $truckA->fresh()->status, 'Truck reserved by an earlier plan must be restored');
        $this->assertSame('available', $truckB->fresh()->status, 'Truck of the failed plan must never be reserved');
        foreach ($harvests as $h) {
            $this->assertSame('sold', $h->fresh()->status->value, 'Harvests must not be left assigned after a rollback');
        }
    }

    private function makeLogisticsUser(): array
    {
        $logistics = User::factory()->logisticsPartner()->create();
        $fp = LogisticsProfile::factory()->create(['user_id' => $logistics->id]);

        return [$logistics, $fp];
    }

    private function makeTruck(LogisticsProfile $fp, int $capacityKg = 5000): Truck
    {
        $driver = User::factory()->driver()->create();
        DriverProfile::create([
            'user_id'           => $driver->id,
            'cooperative_id'    => \App\Models\Cooperative::factory(),
            'partner_id'        => $fp->id,
            'license_no'        => 'DL-' . strtoupper(Str::random(8)),
            'employment_status' => 'active',
        ]);

        return Truck::factory()->state([
            'logistics_profile_id' => $fp->id,
            'capacity_kg'          => $capacityKg,
            'status'               => 'available',
            'driver_id'            => $driver->id,
        ])->create();
    }

    private function makeHarvest(User $farmer, int $qty = 100): Harvest
    {
        $category = CropCategory::firstOrCreate(['name' => 'Grains'], ['status' => 'active']);
        $crop = Crop::firstOrCreate(['name' => 'Rice'], ['crop_category_id' => $category->id, 'status' => 'active']);
        $variety = CropVariety::firstOrCreate(['crop_id' => $crop->id, 'name' => 'IR64'], ['status' => 'active']);

        return Harvest::factory()->state([
            'user_id'               => $farmer->id,
            'crop_category_id'      => $category->id,
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'crop_type'             => $crop->name,
            'variety'               => $variety->name,
            'quantity_kg'           => $qty,
            'remaining_quantity_kg' => $qty,
            'status'                => 'sold',
        ])->create();
    }

    private function completeNegotiation(User $logistics, User $farmer, Harvest $harvest, float $volume): void
    {
        Negotiation::query()->create([
            'buyer_id'          => $logistics->id,
            'farmer_id'         => $farmer->id,
            'harvest_id'        => $harvest->id,
            'status'            => 'COMPLETED',
            'negotiated_price'  => 1000,
            'negotiated_volume' => $volume,
            'last_activity_at'  => now(),
        ]);
    }
}