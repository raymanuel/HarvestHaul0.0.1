<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use App\Models\User;
use App\Models\DriverProfile;
use App\Models\Truck;
use App\Models\LogisticsProfile;
use App\Services\ResourcePoolingService;
use Illuminate\Support\Str;

class ResourcePoolingServicePlanAllTest extends TestCase
{
    use RefreshDatabase;

    private function makeTruck(int $capacityKg, LogisticsProfile $logisticsProfile): Truck
    {
        $driver = User::factory()->create(['role' => 'driver']);
        DriverProfile::create([
            'user_id'   => $driver->id,
            'partner_id' => $logisticsProfile->id,
            'license_no' => 'DL-' . strtoupper(uniqid()),
            'employment_status' => 'active',
        ]);
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

    private function cannedPlan(array $harvestIds): array
    {
        return [
            'success'           => true,
            'message'           => null,
            'selected_harvests' => collect($harvestIds)->map(fn($id) => ['harvest_id' => $id])->values(),
            'truck_id'          => 0,
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

        $service = Mockery::mock(ResourcePoolingService::class)->makePartial();
        $service->shouldReceive('plan')->once()->andReturn(
            $this->cannedPlan([1, 2, 3])
        );

        $result = $service->planAll(
            logisticsProfileId: $lp->id,
            nearbyHarvestIds: [1, 2, 3],
            startLat: 16.0, startLng: 121.0,
            endLat: 15.0, endLng: 120.0,
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

        $service = Mockery::mock(ResourcePoolingService::class)->makePartial();
        $service->shouldReceive('plan')->andReturnUsing(function (Truck $truck, array $ids) {
            if ((int) $truck->capacity_kg >= 5000) {
                return $this->cannedPlan(array_slice($ids, 0, 2));
            }
            return $this->cannedPlan($ids);
        });

        $result = $service->planAll(
            logisticsProfileId: $lp->id,
            nearbyHarvestIds: [1, 2, 3, 4, 5],
            startLat: 16.0, startLng: 121.0,
            endLat: 15.0, endLng: 120.0,
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
}
