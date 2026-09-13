<?php

namespace Tests\Feature;

use App\Models\CustomerCard;
use App\Models\OutboundOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutboundDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_coop_flow_data(): void
    {
        $this->artisan('outbound:demo-seed')->assertSuccessful();

        $this->assertTrue(CustomerCard::exists());
        $this->assertTrue(OutboundOrder::exists());
        $this->assertTrue(OutboundOrder::first()->orderLines()->exists());
        $this->assertTrue(\App\Models\PoolingJob::where('leg_type', 'outbound')->exists());
    }
}