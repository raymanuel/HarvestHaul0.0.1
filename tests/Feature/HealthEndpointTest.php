<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_reports_ok_with_fresh_scraper_heartbeat(): void
    {
        DB::table('scraper_status')->insert([
            'scraper_name'    => 'darfo12',
            'status'          => 'success',
            'source_date'     => now()->toDateString(),
            'message'         => 'test',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJsonPath('database', true)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('scheduler_heartbeat_ok', true);
    }

    public function test_health_is_degraded_when_scraper_heartbeat_is_stale(): void
    {
        DB::table('scraper_status')->insert([
            'scraper_name'    => 'darfo12',
            'status'          => 'success',
            'source_date'     => now()->toDateString(),
            'message'         => 'test',
            'created_at'      => now()->subHours(10),
            'updated_at'      => now()->subHours(10),
        ]);

        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('scheduler_heartbeat_ok', false);
    }

    public function test_health_is_degraded_when_last_scraper_run_failed(): void
    {
        DB::table('scraper_status')->insert([
            'scraper_name'    => 'darfo12',
            'status'          => 'failed',
            'message'         => 'DA Bantay Presyo unreachable',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('last_scraper_status', 'failed');
    }
}
