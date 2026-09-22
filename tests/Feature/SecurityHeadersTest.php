<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every map in the app (location-picker, live-trip-map, delivery trip
     * tracking, coop pickup/outbound planning) loads Leaflet from
     * cdnjs.cloudflare.com. The CSP must allow it or every map silently
     * fails to render — the browser blocks it, the page still returns 200,
     * so nothing server-side (or in a PHPUnit smoke-render) ever catches it.
     */
    public function test_csp_allows_the_leaflet_cdn_that_every_map_component_loads_from(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.show'));

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://cdnjs.cloudflare.com', $csp);
    }
}
