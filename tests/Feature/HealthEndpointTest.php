<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_reports_ok_when_database_is_available(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJsonPath('database', true)
            ->assertJsonPath('status', 'ok');
    }
}
