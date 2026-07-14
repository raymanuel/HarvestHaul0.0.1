<?php

namespace Tests\Feature\Flows;

use Tests\TestCase;

class BasicRoutingTest extends TestCase
{
    public function test_welcome_page_returns_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_terms_page_returns_successful_response(): void
    {
        $response = $this->get('/legal/terms');

        $response->assertStatus(200);
    }

    public function test_privacy_page_returns_successful_response(): void
    {
        $response = $this->get('/legal/privacy');

        $response->assertStatus(200);
    }

    public function test_verification_success_page_returns_successful_response(): void
    {
        $response = $this->get('/email/verified');

        $response->assertStatus(200);
    }

    public function test_nonexistent_route_returns_404(): void
    {
        $response = $this->get('/nonexistent-route');

        $response->assertStatus(404);
    }

    public function test_login_page_returns_successful_response(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_register_index_returns_successful_response(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }
}
