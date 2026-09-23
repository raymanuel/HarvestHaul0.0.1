<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\HaulRequest;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DetectWeatherRiskTest extends TestCase
{
    use RefreshDatabase;

    private function coopWithAdmin(): array
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
            'latitude' => '6.1164', 'longitude' => '125.1716',
        ]);
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);

        return [$coop, $admin];
    }

    private function scheduledJob(Cooperative $coop, string $pickupDate): HaulJob
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $haulRequest = HaulRequest::factory()->create(['farmer_id' => $farmer->id, 'cooperative_id' => $coop->id]);

        return HaulJob::create([
            'cooperative_id' => $coop->id, 'haul_request_id' => $haulRequest->id,
            'pickup_date' => $pickupDate, 'status' => HaulJob::STATUS_SCHEDULED,
        ]);
    }

    private function weatherFake(int $precipitation, float $wind): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'hourly' => [
                    'time' => [today()->toDateString().'T12:00'],
                    'precipitation_probability' => [$precipitation],
                    'weathercode' => [61],
                    'windspeed_10m' => [$wind],
                ],
            ], 200),
        ]);
    }

    public function test_a_trip_under_severe_weather_gets_flagged_and_coop_notified(): void
    {
        [$coop, $admin] = $this->coopWithAdmin();
        $job = $this->scheduledJob($coop, today()->toDateString());
        $this->weatherFake(85, 10);

        $this->artisan('haul:detect-weather-risk')->assertExitCode(0);

        $job->refresh();
        $this->assertNotNull($job->weather_alerted_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'category' => 'haul']);
    }

    public function test_a_trip_under_clear_weather_is_not_flagged(): void
    {
        [$coop, $admin] = $this->coopWithAdmin();
        $job = $this->scheduledJob($coop, today()->toDateString());
        $this->weatherFake(5, 10);

        $this->artisan('haul:detect-weather-risk');

        $this->assertNull($job->refresh()->weather_alerted_at);
        $this->assertDatabaseMissing('notifications', ['user_id' => $admin->id]);
    }

    public function test_an_already_alerted_trip_is_never_notified_twice(): void
    {
        [$coop, $admin] = $this->coopWithAdmin();
        $job = $this->scheduledJob($coop, today()->toDateString());
        $job->update(['weather_alerted_at' => now()]);
        $this->weatherFake(85, 10);

        $this->artisan('haul:detect-weather-risk');

        $this->assertDatabaseMissing('notifications', ['user_id' => $admin->id]);
    }

    public function test_a_completed_trip_is_not_checked(): void
    {
        [$coop, $admin] = $this->coopWithAdmin();
        $job = $this->scheduledJob($coop, today()->toDateString());
        $job->update(['status' => HaulJob::STATUS_COMPLETED]);
        $this->weatherFake(85, 10);

        $this->artisan('haul:detect-weather-risk');

        $this->assertNull($job->refresh()->weather_alerted_at);
    }
}
