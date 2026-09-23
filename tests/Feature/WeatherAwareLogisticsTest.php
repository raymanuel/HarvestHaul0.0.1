<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\HaulRequest;
use App\Models\Truck;
use App\Services\Cooperative\ConsolidationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherAwareLogisticsTest extends TestCase
{
    use RefreshDatabase;

    private function cooperative(): Cooperative
    {
        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
            'latitude' => '6.1164', 'longitude' => '125.1716',
        ]);
    }

    private function osrmFake(): array
    {
        return [
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok',
                'durations' => [[0, 300], [300, 0]],
                'distances' => [[0, 5000], [5000, 0]],
            ]),
        ];
    }

    private function weatherFake(int $precipitation, float $wind): array
    {
        return [
            'api.open-meteo.com/*' => Http::response($this->weatherBody($precipitation, $wind), 200),
        ];
    }

    private function weatherBody(int $precipitation, float $wind): array
    {
        return [
            'hourly' => [
                'time' => ['2026-11-06T12:00'],
                'precipitation_probability' => [$precipitation],
                'weathercode' => [61],
                'windspeed_10m' => [$wind],
            ],
        ];
    }

    /**
     * The additive-change guarantee: when weather is unavailable (or clear),
     * the buffer is exactly 1.0 — planning behaves identically to before
     * this feature existed.
     */
    public function test_plan_for_date_buffer_is_1_when_weather_is_unavailable(): void
    {
        Http::fake(array_merge($this->osrmFake(), [
            'api.open-meteo.com/*' => Http::response([], 500),
        ]));

        $coop = $this->cooperative();
        $date = '2026-11-06';
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);
        HaulRequest::factory()->create([
            'cooperative_id' => $coop->id, 'status' => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date, 'estimated_weight_kg' => 1000,
            'pickup_location_lat' => 6.12, 'pickup_location_lng' => 125.18,
        ]);

        $plan = app(ConsolidationEngine::class)->planForDate($coop, $date);

        $this->assertEquals(1.0, $plan['weather']['buffer']);
        $this->assertEquals('unknown', $plan['weather']['severity']);
        $this->assertNull($plan['weather']['forecast']);
    }

    public function test_severe_weather_produces_later_arrival_times_than_clear_weather(): void
    {
        $coop = $this->cooperative();
        $date = '2026-11-06';
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);
        HaulRequest::factory()->create([
            'cooperative_id' => $coop->id, 'status' => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date, 'estimated_weight_kg' => 1000,
            'pickup_location_lat' => 6.12, 'pickup_location_lng' => 125.18,
        ]);

        Http::fake(array_merge($this->osrmFake(), [
            'api.open-meteo.com/*' => Http::sequence()
                ->push($this->weatherBody(5, 10))
                ->push($this->weatherBody(85, 10)),
        ]));

        $clearPlan = app(ConsolidationEngine::class)->planForDate($coop, $date);
        \Illuminate\Support\Facades\Cache::flush();
        $severePlan = app(ConsolidationEngine::class)->planForDate($coop, $date);

        $this->assertEquals('clear', $clearPlan['weather']['severity']);
        $this->assertEquals(1.0, $clearPlan['weather']['buffer']);
        $this->assertEquals('severe', $severePlan['weather']['severity']);
        $this->assertEquals(1.30, $severePlan['weather']['buffer']);

        $clearArrival = $clearPlan['groups'][0]['proposed']['schedule'][0]['arrival_min'];
        $severeArrival = $severePlan['groups'][0]['proposed']['schedule'][0]['arrival_min'];

        $this->assertGreaterThan($clearArrival, $severeArrival);
    }

    public function test_outbound_delivery_planning_also_carries_a_weather_advisory(): void
    {
        Http::fake(array_merge($this->osrmFake(), $this->weatherFake(5, 10)));

        $coop = $this->cooperative();
        $date = '2026-11-06';
        Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);

        $plan = app(ConsolidationEngine::class)->planDeliveriesForDate($coop, $date);

        $this->assertArrayHasKey('weather', $plan);
        $this->assertEquals('clear', $plan['weather']['severity']);
    }
}
