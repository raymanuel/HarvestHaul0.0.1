<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use App\Models\User;
use App\Models\DriverProfile;
use App\Models\Harvest;
use App\Models\LogisticsProfile;
use App\Models\Negotiation;
use App\Models\PoolingJob;
use App\Models\Truck;
use App\Services\ResourcePoolingService;
use Illuminate\Support\Str;

class ResourcePoolingServicePlanAllTest extends TestCase
{
    use RefreshDatabase;

    private function makeTruck(int $capacityKg, LogisticsProfile $logisticsProfile, ?int $driverId = null): Truck
    {
        $driver = $driverId !== null
            ? User::findOrFail($driverId)
            : User::factory()->create(['role' => 'driver']);
        DriverProfile::firstOrCreate(
            ['user_id' => $driver->id],
            [
                'partner_id' => $logisticsProfile->id,
                'license_no' => 'DL-' . strtoupper(uniqid()),
                'employment_status' => 'active',
            ]
        );
        $truck = Truck::create([
            'logistics_profile_id' => $logisticsProfile->id,
            'truck_name'           => 'Truck ' . $capacityKg,
            'plate_number'         => 'TKA-' . $capacityKg . '-' . Str::random(4),
            'capacity_kg'          => $capacityKg,
            'status'               => 'available',
            'driver_id'            => $driver->id,
        ]);
        return $truck;
    }

    private function cannedPlan(array $harvestIds, int $truckId = 0): array
    {
        return [
            'success'           => true,
            'message'           => null,
            'selected_harvests' => collect($harvestIds)->map(fn($id) => ['harvest_id' => $id])->values(),
            'truck_id'          => $truckId,
            'selected_count'    => count($harvestIds),
            'requested_count'   => count($harvestIds),
            'total_kg'          => 0,
            'stops'             => [],
            'capacity_kg'       => 0,
        ];
    }

    public function test_plan_all_single_route_when_all_harvests_fit(): void
    {
        $lp = LogisticsProfile::factory()->create();
        $this->makeTruck(10000, $lp);

        $harvests = Harvest::factory()->count(3)->create([
            'status'    => 'sold',
            'latitude'  => 8.0,
            'longitude' => 125.0,
        ]);
        foreach ($harvests as $harvest) {
            Negotiation::factory()->completed()->create([
                'harvest_id' => $harvest->id,
                'farmer_id'  => $harvest->user_id,
            ]);
        }
        $ids = $harvests->pluck('id')->all();

        $service = Mockery::mock(ResourcePoolingService::class)->makePartial();
        $service->shouldReceive('plan')->once()->andReturn(
            $this->cannedPlan($ids)
        );

        $result = $service->planAll(
            logisticsProfileId: $lp->id,
            nearbyHarvestIds: $ids,
            startLat: 8.0, startLng: 125.0,
            endLat: 8.0, endLng: 125.0,
            radiusKm: 100,
            haulingRatePerKg: 10,
        );

        $this->assertFalse($result['overflow']);
        $this->assertCount(1, $result['plans']);
        $this->assertEquals(3, $result['selected_total']);
        $this->assertEquals(3, $result['total_farms']);
        $this->assertEquals(0, $result['unassigned']);
    }

    public function test_plan_all_splits_across_two_trucks_on_overflow(): void
    {
        $lp = LogisticsProfile::factory()->create();
        $this->makeTruck(5000, $lp);
        $this->makeTruck(2000, $lp);

        $harvests = Harvest::factory()->count(5)->create([
            'status'    => 'sold',
            'latitude'  => 8.0,
            'longitude' => 125.0,
        ]);
        foreach ($harvests as $harvest) {
            Negotiation::factory()->completed()->create([
                'harvest_id' => $harvest->id,
                'farmer_id'  => $harvest->user_id,
            ]);
        }
        $ids = $harvests->pluck('id')->sort()->values()->all();

        $service = Mockery::mock(ResourcePoolingService::class)->makePartial();
        $service->shouldReceive('plan')->andReturnUsing(function (Truck $truck, array $nearbyIds) {
            if ((int) $truck->capacity_kg >= 5000) {
                return $this->cannedPlan(array_slice($nearbyIds, 0, 2));
            }
            return $this->cannedPlan($nearbyIds);
        });

        $result = $service->planAll(
            logisticsProfileId: $lp->id,
            nearbyHarvestIds: $ids,
            startLat: 8.0, startLng: 125.0,
            endLat: 8.0, endLng: 125.0,
            radiusKm: 100,
            haulingRatePerKg: 10,
        );

        $this->assertTrue($result['overflow']);
        $this->assertCount(2, $result['plans']);
        $this->assertEquals(5, $result['selected_total']);
        $this->assertEquals(5, $result['total_farms']);
        $this->assertEquals(0, $result['unassigned']);

        $assigned = collect($result['plans'])
            ->flatMap(fn($p) => collect($p['selected_harvests'])->pluck('harvest_id'))
            ->sort()->values()->all();
        $this->assertEquals([1, 2, 3, 4, 5], $assigned);
    }

    public function test_plan_all_returns_empty_when_no_available_trucks(): void
    {
        $service = Mockery::mock(ResourcePoolingService::class)->makePartial();
        $service->shouldNotReceive('plan');

        $result = $service->planAll(
            logisticsProfileId: 999,
            nearbyHarvestIds: [1, 2],
            startLat: 16.0, startLng: 121.0,
            endLat: 15.0, endLng: 120.0,
            radiusKm: 100,
        );

        $this->assertCount(0, $result['plans']);
        $this->assertNotNull($result['message']);
        $this->assertEquals(2, $result['unassigned']);
    }

    public function test_plan_all_excludes_farm_without_completed_negotiation(): void
    {
        $lp = LogisticsProfile::factory()->create();
        $this->makeTruck(10000, $lp);

        $good = Harvest::factory()->create(['status' => 'sold', 'latitude' => 8.0, 'longitude' => 125.0]);
        Negotiation::factory()->completed()->create([
            'harvest_id' => $good->id,
            'farmer_id'  => $good->user_id,
        ]);

        $bad = Harvest::factory()->create(['status' => 'sold', 'latitude' => 8.0, 'longitude' => 125.0]);

        $service = Mockery::mock(ResourcePoolingService::class)->makePartial();
        $service->shouldReceive('plan')->once()->andReturn($this->cannedPlan([$good->id]));

        $result = $service->planAll(
            logisticsProfileId: $lp->id,
            nearbyHarvestIds: [$good->id, $bad->id],
            startLat: 8.0, startLng: 125.0,
            endLat: 8.0, endLng: 125.0,
            radiusKm: 100,
            haulingRatePerKg: 10,
        );

        $this->assertArrayHasKey('excluded', $result);
        $this->assertCount(1, $result['excluded']);
        $this->assertEquals($bad->id, $result['excluded'][0]['harvest_id']);
        $this->assertArrayHasKey('farm_name', $result['excluded'][0]);
        $this->assertStringContainsString('agreement', strtolower($result['excluded'][0]['reason']));
        $this->assertNotEmpty($result['plans']);
    }

    public function test_plan_all_excludes_farm_beyond_radius(): void
    {
        $lp = LogisticsProfile::factory()->create();
        $this->makeTruck(10000, $lp);

        $near = Harvest::factory()->create(['status' => 'sold', 'latitude' => 8.0, 'longitude' => 125.0]);
        Negotiation::factory()->completed()->create([
            'harvest_id' => $near->id,
            'farmer_id'  => $near->user_id,
        ]);

        $far = Harvest::factory()->create(['status' => 'sold', 'latitude' => 8.0, 'longitude' => 125.0]);
        Negotiation::factory()->completed()->create([
            'harvest_id' => $far->id,
            'farmer_id'  => $far->user_id,
        ]);

        $service = Mockery::mock(ResourcePoolingService::class)->makePartial();
        $service->shouldReceive('plan')->once()->andReturn($this->cannedPlan([$near->id]));

        $result = $service->planAll(
            logisticsProfileId: $lp->id,
            nearbyHarvestIds: [$near->id, $far->id],
            startLat: 8.0, startLng: 125.0,
            endLat: 8.0, endLng: 125.0,
            radiusKm: 100,
            haulingRatePerKg: 10,
            farmDistances: [$far->id => 500],
        );

        $this->assertArrayHasKey('excluded', $result);
        $this->assertCount(1, $result['excluded']);
        $this->assertEquals($far->id, $result['excluded'][0]['harvest_id']);
        $this->assertStringContainsString('too far', strtolower($result['excluded'][0]['reason']));
        $this->assertNotEmpty($result['plans']);
    }

    public function test_plan_all_excludes_farm_beyond_radius_via_haversine(): void
    {
        $lp = LogisticsProfile::factory()->create();
        $this->makeTruck(10000, $lp);

        $near = Harvest::factory()->create(['status' => 'sold', 'latitude' => 8.0, 'longitude' => 125.0]);
        Negotiation::factory()->completed()->create([
            'harvest_id' => $near->id,
            'farmer_id'  => $near->user_id,
        ]);

        $far = Harvest::factory()->create(['status' => 'sold', 'latitude' => 8.0, 'longitude' => 126.5]);
        Negotiation::factory()->completed()->create([
            'harvest_id' => $far->id,
            'farmer_id'  => $far->user_id,
        ]);

        $service = Mockery::mock(ResourcePoolingService::class)->makePartial();
        $service->shouldReceive('plan')->once()->andReturn($this->cannedPlan([$near->id]));

        $result = $service->planAll(
            logisticsProfileId: $lp->id,
            nearbyHarvestIds: [$near->id, $far->id],
            startLat: 8.0, startLng: 125.0,
            endLat: 8.0, endLng: 125.0,
            radiusKm: 100,
            haulingRatePerKg: 10,
        );

        $this->assertCount(1, $result['excluded']);
        $this->assertEquals($far->id, $result['excluded'][0]['harvest_id']);
        $this->assertStringContainsString('too far', strtolower($result['excluded'][0]['reason']));
        $this->assertNotEmpty($result['plans']);
    }

    public function test_plan_all_excludes_farm_already_on_pending_pooling_job(): void
    {
        $lp = LogisticsProfile::factory()->create();
        $truck = $this->makeTruck(10000, $lp);

        $good = Harvest::factory()->create(['status' => 'sold', 'latitude' => 8.0, 'longitude' => 125.0]);
        Negotiation::factory()->completed()->create([
            'harvest_id' => $good->id,
            'farmer_id'  => $good->user_id,
        ]);

        $taken = Harvest::factory()->create(['status' => 'sold', 'latitude' => 8.0, 'longitude' => 125.0]);
        Negotiation::factory()->completed()->create([
            'harvest_id' => $taken->id,
            'farmer_id'  => $taken->user_id,
        ]);

        $job = PoolingJob::factory()->create([
            'logistics_profile_id' => $lp->id,
            'truck_id'             => $truck->id,
            'driver_id'            => $truck->driver_id,
            'status'               => 'pending',
        ]);
        $job->harvests()->attach($taken->id, [
            'pickup_order' => 1,
            'quantity_kg'  => 100,
            'status'       => 'pending',
        ]);

        $service = Mockery::mock(ResourcePoolingService::class)->makePartial();
        $service->shouldReceive('plan')->once()->andReturn($this->cannedPlan([$good->id]));

        $result = $service->planAll(
            logisticsProfileId: $lp->id,
            nearbyHarvestIds: [$good->id, $taken->id],
            startLat: 8.0, startLng: 125.0,
            endLat: 8.0, endLng: 125.0,
            radiusKm: 100,
            haulingRatePerKg: 10,
        );

        $this->assertArrayHasKey('excluded', $result);
        $this->assertCount(1, $result['excluded']);
        $this->assertEquals($taken->id, $result['excluded'][0]['harvest_id']);
        $this->assertStringContainsString('already on another', strtolower($result['excluded'][0]['reason']));
        $this->assertNotEmpty($result['plans']);
    }

    public function test_plan_all_dedupes_driver_across_trucks(): void
    {
        $lp = LogisticsProfile::factory()->create();
        $bigTruck = $this->makeTruck(10000, $lp);
        $this->makeTruck(2000, $lp, driverId: $bigTruck->driver_id);

        $harvest = Harvest::factory()->create(['status' => 'sold', 'latitude' => 8.0, 'longitude' => 125.0]);
        Negotiation::factory()->completed()->create([
            'harvest_id' => $harvest->id,
            'farmer_id'  => $harvest->user_id,
        ]);

        $service = Mockery::mock(ResourcePoolingService::class)->makePartial();
        $service->shouldReceive('plan')->once()->andReturn($this->cannedPlan([$harvest->id], $bigTruck->id));

        $result = $service->planAll(
            logisticsProfileId: $lp->id,
            nearbyHarvestIds: [$harvest->id],
            startLat: 8.0, startLng: 125.0,
            endLat: 8.0, endLng: 125.0,
            radiusKm: 100,
            haulingRatePerKg: 10,
        );

        $this->assertCount(1, $result['plans']);
        $this->assertEquals($bigTruck->id, $result['plans'][0]['truck_id']);
    }
}
