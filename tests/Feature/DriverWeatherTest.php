<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\Harvest;
use App\Models\LogisticsProfile;
use App\Models\PoolingJob;
use App\Models\Truck;
use App\Models\User;
use App\Models\WeatherLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DriverWeatherTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedLogistics(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name'       => 'Test Logistics',
            'business_permit_no' => 'BL-12345',
            'phone'              => '09123456789',
            'is_verified'        => true,
            'logistics_type'     => 'company',
        ]);
        return $user;
    }

    private function createDriver(): User
    {
        $user = User::factory()->driver()->create(['email_verified_at' => now()]);
        $user->driverProfile()->create([
            'partner_id'        => LogisticsProfile::factory()->create()->id,
            'license_no'        => 'L-1234567',
            'employment_status' => 'active',
            'identity_verified' => true,
        ]);
        return $user;
    }

    private function createJob(User $logistics, User $driver, array $overrides = []): PoolingJob
    {
        $truck = Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'truck_name'           => 'Test Truck',
            'plate_number'         => 'ABC-1234',
            'capacity_kg'          => 5000,
            'status'               => 'reserved',
            'vehicle_type'         => 'truck',
        ]);

        return PoolingJob::factory()->confirmed()->create(array_merge([
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'driver_id'            => $driver->id,
            'truck_id'             => $truck->id,
            'accepted_at'          => now(),
            'start_latitude'       => 8.9475,
            'start_longitude'      => 125.5406,
        ], $overrides));
    }

    private function fakeWeatherApi(): void
    {
        $payload = [
            'weather' => [['main' => 'Rain', 'description' => 'light rain', 'icon' => '10d']],
            'main'    => ['temp' => 24.0, 'feels_like' => 23.0, 'humidity' => 88],
            'wind'    => ['speed' => 4.1, 'gust' => 6.0],
            'visibility' => 8000,
        ];

        Http::fake([
            'api.openweathermap.org/data/2.5/weather*' => Http::response($payload, 200),
            'api.openweathermap.org/data/2.5/forecast*' => Http::response([
                'list' => [
                    ['dt' => now()->addHours(1)->timestamp, 'main' => ['temp' => 24.0], 'weather' => [['main' => 'Rain']]],
                    ['dt' => now()->addHours(3)->timestamp, 'main' => ['temp' => 24.0], 'weather' => [['main' => 'Rain']]],
                ],
            ], 200),
        ]);
    }

    public function test_starting_trip_persists_weather_onto_job(): void
    {
        $this->fakeWeatherApi();

        $logistics = $this->createVerifiedLogistics();
        $driver = $this->createDriver();
        $job = $this->createJob($logistics, $driver);

        $this->actingAs($driver)->patch(route('driver.jobs.status', $job))
            ->assertSessionHas('success');

        $fresh = $job->fresh();
        $this->assertSame('in_progress', $fresh->status->value);
        $this->assertNotNull($fresh->weather_condition);
        $this->assertTrue(
            $this->assertWeatherColumnPopulated($fresh),
            'Weather columns were not persisted onto the pooling job during trip start.'
        );
    }

    private function assertWeatherColumnPopulated(PoolingJob $job): bool
    {
        return $job->weather_condition !== null
            || $job->weather_temperature !== null
            || $job->weather_wind_speed !== null
            || $job->weather_icon !== null;
    }

    public function test_trip_start_still_succeeds_when_weather_api_unavailable(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response([], 500),
        ]);

        $logistics = $this->createVerifiedLogistics();
        $driver = $this->createDriver();
        $job = $this->createJob($logistics, $driver);

        $this->actingAs($driver)->patch(route('driver.jobs.status', $job))
            ->assertSessionHas('success');

        $this->assertSame('in_progress', $job->fresh()->status->value);
    }

    public function test_job_show_passes_weather_and_eta_to_view(): void
    {
        $logistics = $this->createVerifiedLogistics();
        $driver = $this->createDriver();
        $job = $this->createJob($logistics, $driver, ['status' => 'in_progress']);

        WeatherLog::create([
            'pooling_job_id' => $job->id,
            'latitude'       => 8.9475,
            'longitude'      => 125.5406,
            'condition'      => 'Clear',
            'description'    => 'clear sky',
            'temperature'    => 27.0,
            'wind_speed'     => 2.0,
            'humidity'       => 60,
            'is_severe'      => false,
            'checked_at'     => now(),
        ]);

        $response = $this->actingAs($driver)->get(route('driver.jobs.show', $job));
        $response->assertOk();
        $response->assertViewHas('weatherLog');
        $response->assertViewHas('eta');
    }

    public function test_dashboard_renders_with_weather_badge(): void
    {
        $logistics = $this->createVerifiedLogistics();
        $driver = $this->createDriver();
        $job = $this->createJob($logistics, $driver, ['status' => 'in_progress']);

        WeatherLog::create([
            'pooling_job_id' => $job->id,
            'latitude'       => 8.9475,
            'longitude'      => 125.5406,
            'condition'      => 'Clear',
            'description'    => 'clear sky',
            'temperature'    => 27.0,
            'wind_speed'     => 2.0,
            'humidity'       => 60,
            'is_severe'      => false,
            'checked_at'     => now(),
        ]);

        $this->actingAs($driver)->get(route('driver.dashboard'))->assertOk();
    }
}
