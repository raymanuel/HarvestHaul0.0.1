<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\PoolingJob;

class PoolingJobTest extends TestCase
{
    public function test_load_percentage_zero_when_capacity_is_null(): void
    {
        $job = new PoolingJob();
        $job->setAttribute('total_kg', 500);

        $this->assertSame(0.0, $job->getLoadPercentageAttribute());
    }

    public function test_load_percentage_half_load(): void
    {
        $job = new PoolingJob();
        $job->setAttribute('total_kg', 500);
        $job->setAttribute('truck_capacity_kg', 1000);

        $this->assertSame(50.0, $job->getLoadPercentageAttribute());
    }

    public function test_load_percentage_full_load(): void
    {
        $job = new PoolingJob();
        $job->setAttribute('total_kg', 1000);
        $job->setAttribute('truck_capacity_kg', 1000);

        $this->assertSame(100.0, $job->getLoadPercentageAttribute());
    }

    public function test_load_percentage_rounds_to_one_decimal(): void
    {
        $job = new PoolingJob();
        $job->setAttribute('total_kg', 333);
        $job->setAttribute('truck_capacity_kg', 1000);

        $this->assertSame(33.3, $job->getLoadPercentageAttribute());
    }

    public function test_load_percentage_over_load(): void
    {
        $job = new PoolingJob();
        $job->setAttribute('total_kg', 1200);
        $job->setAttribute('truck_capacity_kg', 1000);

        $this->assertSame(120.0, $job->getLoadPercentageAttribute());
    }

    public function test_load_percentage_zero_total_kg(): void
    {
        $job = new PoolingJob();
        $job->setAttribute('total_kg', 0);
        $job->setAttribute('truck_capacity_kg', 1000);

        $this->assertSame(0.0, $job->getLoadPercentageAttribute());
    }
}
