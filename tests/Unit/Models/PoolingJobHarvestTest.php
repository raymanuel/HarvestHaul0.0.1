<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Carbon\Carbon;
use App\Models\PoolingJobHarvest;

class PoolingJobHarvestTest extends TestCase
{
    public function test_carbon_diff_works_directly(): void
    {
        $a = Carbon::parse('2026-07-10 13:30:00');
        $b = Carbon::parse('2026-07-10 13:40:00');
        $this->assertEquals(10, abs($b->diffInMinutes($a)));
    }

    public function test_stop_duration_all_timestamps_present(): void
    {
        $pivot = new PoolingJobHarvest();

        // Write directly to attributes array
        $ref = new \ReflectionProperty(PoolingJobHarvest::class, 'attributes');
        $ref->setAccessible(true);
        $ref->setValue($pivot, [
            'arrived_at' => '2026-07-10 13:30:00',
            'loaded_at' => '2026-07-10 13:40:00',
            'delivered_at' => '2026-07-10 13:55:00',
            'created_at' => '2026-07-10 13:00:00',
        ]);

        $duration = $pivot->getStopDurationAttribute();

        $this->assertIsArray($duration);
        $this->assertEquals(10, $duration['loading_dock']);
        $this->assertEquals(15, $duration['delivery_run']);
        $this->assertEquals(25, $duration['total_stop']);
        $this->assertEquals(30, $duration['travel_to_farm']);
    }

    public function test_stop_duration_no_timestamps(): void
    {
        $pivot = new PoolingJobHarvest();
        $duration = $pivot->getStopDurationAttribute();

        $this->assertNull($duration['travel_to_farm']);
        $this->assertNull($duration['loading_dock']);
        $this->assertNull($duration['delivery_run']);
        $this->assertNull($duration['total_stop']);
    }

    public function test_stop_duration_partial_timestamps(): void
    {
        $pivot = new PoolingJobHarvest();
        $ref = new \ReflectionProperty(PoolingJobHarvest::class, 'attributes');
        $ref->setAccessible(true);
        $ref->setValue($pivot, [
            'arrived_at' => '2026-07-10 13:30:00',
            'loaded_at' => '2026-07-10 13:40:00',
        ]);

        $duration = $pivot->getStopDurationAttribute();

        $this->assertNull($duration['travel_to_farm']); // no created_at
        $this->assertEquals(10, $duration['loading_dock']);
        $this->assertNull($duration['delivery_run']);
        $this->assertNull($duration['total_stop']);
    }

    public function test_stop_duration_human_full(): void
    {
        $pivot = new PoolingJobHarvest();
        $ref = new \ReflectionProperty(PoolingJobHarvest::class, 'attributes');
        $ref->setAccessible(true);
        $ref->setValue($pivot, [
            'arrived_at' => '2026-07-10 13:00:00',
            'loaded_at' => '2026-07-10 13:55:00',
            'delivered_at' => '2026-07-10 14:00:00',
        ]);

        $human = $pivot->getStopDurationHumanAttribute();

        $this->assertNotNull($human);
        $this->assertEquals('55 min', $human['loading_dock']);
        $this->assertEquals('5 min', $human['delivery_run']);
    }

    public function test_stop_duration_human_less_than_one_minute(): void
    {
        $pivot = new PoolingJobHarvest();
        $ref = new \ReflectionProperty(PoolingJobHarvest::class, 'attributes');
        $ref->setAccessible(true);
        $ref->setValue($pivot, [
            'arrived_at' => '2026-07-10 14:00:00',
            'loaded_at' => '2026-07-10 14:00:30',
        ]);

        $human = $pivot->getStopDurationHumanAttribute();

        $this->assertEquals('<1 min', $human['loading_dock']);
    }
}
