<?php

namespace Tests\Unit;

use App\Services\Geocoding\NominatimGeocodingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NominatimGeocodingServiceTest extends TestCase
{
    public function test_reverse_returns_a_display_name_on_success(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'display_name' => 'Purok 3, Barangay Fatima, General Santos, South Cotabato, Philippines',
            ], 200),
        ]);

        $service = new NominatimGeocodingService();
        $address = $service->reverse(6.1164, 125.1716);

        $this->assertEquals('Purok 3, Barangay Fatima, General Santos, South Cotabato, Philippines', $address);
    }

    public function test_reverse_returns_null_on_a_failed_response_instead_of_throwing(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 500),
        ]);

        $service = new NominatimGeocodingService();
        $address = $service->reverse(6.1164, 125.1716);

        $this->assertNull($address);
    }

    public function test_reverse_returns_null_when_the_request_throws(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('timed out');
        });

        $service = new NominatimGeocodingService();
        $address = $service->reverse(6.1164, 125.1716);

        $this->assertNull($address);
    }

    public function test_reverse_caches_so_the_same_coordinates_only_hit_the_service_once(): void
    {
        Cache::flush();

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response(['display_name' => 'Cached Address'], 200),
        ]);

        $service = new NominatimGeocodingService();
        $service->reverse(6.116401, 125.171601);
        $service->reverse(6.116403, 125.171603); // rounds to the same 5-decimal cache key

        Http::assertSentCount(1);
    }

    public function test_reverse_sends_an_identifying_user_agent(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response(['display_name' => 'X'], 200),
        ]);

        $service = new NominatimGeocodingService();
        $service->reverse(1.0, 2.0);

        Http::assertSent(function ($request) {
            return $request->hasHeader('User-Agent')
                && str_contains($request->header('User-Agent')[0], 'HarvestHaul');
        });
    }
}
