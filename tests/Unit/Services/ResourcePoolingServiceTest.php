<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ResourcePoolingService;
use Illuminate\Support\Collection;

class ResourcePoolingServiceTest extends TestCase
{
    private function callPrivateMethod(object $object, string $methodName, array $params = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $params);
    }

    private function makeHarvest(int $id, float $kg, float $lat = 16.0, float $lng = 121.0): object
    {
        return (object) [
            'id' => $id,
            'quantity_kg' => $kg,
            'latitude' => $lat,
            'longitude' => $lng,
        ];
    }

    public function test_knapsack_selects_single_item_that_fits(): void
    {
        $service = new ResourcePoolingService();
        $harvests = new Collection([$this->makeHarvest(1, 500)]);
        $selected = $this->callPrivateMethod($service, 'knapsack', [$harvests, 1000]);

        $this->assertCount(1, $selected);
        $this->assertEquals(500, $selected->first()->quantity_kg);
    }

    public function test_knapsack_selects_exact_fit_over_greedy(): void
    {
        $service = new ResourcePoolingService();
        // Greedy would pick 800 (too big for 1000 alone, so picks 600+300=900).
        // Optimal picks 500+500=1000 (exact fit)
        $harvests = new Collection([
            $this->makeHarvest(1, 600),
            $this->makeHarvest(2, 500),
            $this->makeHarvest(3, 500),
            $this->makeHarvest(4, 300),
        ]);
        $selected = $this->callPrivateMethod($service, 'knapsack', [$harvests, 1000]);

        $totalKg = $selected->sum('quantity_kg');
        $this->assertEquals(1000, $totalKg);
        $this->assertCount(2, $selected);
    }

    public function test_knapsack_returns_empty_when_none_fit(): void
    {
        $service = new ResourcePoolingService();
        $harvests = new Collection([$this->makeHarvest(1, 2000)]);
        $selected = $this->callPrivateMethod($service, 'knapsack', [$harvests, 1000]);

        $this->assertCount(0, $selected);
    }

    public function test_knapsack_empty_harvests(): void
    {
        $service = new ResourcePoolingService();
        $selected = $this->callPrivateMethod($service, 'knapsack', [new Collection(), 1000]);

        $this->assertCount(0, $selected);
    }

    public function test_knapsack_selects_heaviest_subset_when_tie(): void
    {
        $service = new ResourcePoolingService();
        // Two options: 400+400=800 (2 items) or 800 alone (1 item).
        // Both have same weight, so the one with more items wins.
        $harvests = new Collection([
            $this->makeHarvest(1, 800),
            $this->makeHarvest(2, 400),
            $this->makeHarvest(3, 400),
        ]);
        $selected = $this->callPrivateMethod($service, 'knapsack', [$harvests, 1000]);

        $this->assertEquals(800, $selected->sum('quantity_kg'));
        $this->assertCount(2, $selected);
    }

    public function test_knapsack_single_harvest_fits(): void
    {
        $service = new ResourcePoolingService();
        $harvests = new Collection([$this->makeHarvest(1, 500)]);
        $selected = $this->callPrivateMethod($service, 'knapsack', [$harvests, 500]);

        $this->assertCount(1, $selected);
        $this->assertEquals(500, $selected->first()->quantity_kg);
    }

    public function test_knapsack_single_harvest_too_big(): void
    {
        $service = new ResourcePoolingService();
        $harvests = new Collection([$this->makeHarvest(1, 1001)]);
        $selected = $this->callPrivateMethod($service, 'knapsack', [$harvests, 1000]);

        $this->assertCount(0, $selected);
    }
}
