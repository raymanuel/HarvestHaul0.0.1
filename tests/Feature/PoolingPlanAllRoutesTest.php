<?php

namespace Tests\Feature;

use Tests\TestCase;

class PoolingPlanAllRoutesTest extends TestCase
{
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
}
