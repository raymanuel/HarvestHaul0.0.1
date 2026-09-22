<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\Harvest;
use App\Models\Notification;
use App\Models\PoolingJob;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DriverHaulDayTest extends TestCase
{
    use RefreshDatabase;

    private function createLogisticsUser(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name' => 'Test Logistics',
            'business_permit_no' => 'BL-'.random_int(100000, 999999),
            'phone' => '09123456789',
            'is_verified' => true,
            'logistics_type' => 'company',
        ]);

        return $user;
    }

    private function createDriverOnTeam(User $logistics, string $name = 'Team Driver'): User
    {
        $user = User::factory()->driver()->create(['email_verified_at' => now(), 'name' => $name]);
        $user->driverProfile()->create([
            'partner_id' => $logistics->logisticsProfile->id,
            'license_no' => 'L-'.$user->id,
            'employment_status' => 'active',
            'identity_verified' => true,
        ]);

        return $user;
    }

    private function createTruck(User $logistics): Truck
    {
        return Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'truck_name' => 'Test Truck',
            'plate_number' => 'ABC-'.random_int(1000, 9999),
            'capacity_kg' => 5000,
            'status' => 'reserved',
            'vehicle_type' => 'truck',
        ]);
    }

    private function createHarvest(string $harvestDate, float $quantityKg = 100): Harvest
    {
        $category = CropCategory::firstOrCreate(['name' => 'Grains'], ['status' => 'active']);
        $crop = Crop::firstOrCreate(['name' => 'Rice'], ['crop_category_id' => $category->id, 'status' => 'active']);
        $variety = CropVariety::firstOrCreate(['crop_id' => $crop->id, 'name' => 'Standard'], ['status' => 'active']);

        return Harvest::create([
            'user_id' => User::factory()->farmer()->create()->id,
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'crop_category_id' => $category->id,
            'crop_type' => $crop->name,
            'variety' => $variety->name,
            'quantity_kg' => $quantityKg,
            'remaining_quantity_kg' => $quantityKg,
            'unit' => 'kg',
            'status' => 'active',
            'harvest_date' => $harvestDate,
            'pickup_window_start' => '08:00:00',
            'pickup_window_end' => '10:00:00',
            'destination_address' => 'Test',
            'destination_latitude' => 7.0,
            'destination_longitude' => 125.0,
        ]);
    }

    private function createJob(User $logistics, ?User $driver, string $harvestDate, array $overrides = []): PoolingJob
    {
        $truck = $this->createTruck($logistics);

        $job = PoolingJob::factory()->confirmed()->create(array_merge([
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'truck_id' => $truck->id,
            'driver_id' => $driver?->id,
            'accepted_at' => null,
        ], $overrides));

        $job->harvests()->attach($this->createHarvest($harvestDate)->id, [
            'pickup_order' => 1, 'quantity_kg' => 100, 'status' => 'pending', 'cost_share' => 500,
        ]);

        return $job;
    }

    private function fakeWeatherApi(): void
    {
        Http::fake([
            'api.openweathermap.org/*' => Http::response([
                'weather' => [['main' => 'Clear', 'description' => 'clear sky', 'icon' => '01d']],
                'main' => ['temp' => 28.0, 'feels_like' => 30.0, 'humidity' => 70],
                'wind' => ['speed' => 3.0, 'gust' => 5.0],
                'visibility' => 10000,
            ], 200),
        ]);
    }

    // ─────────────────────────────────────────────────
    // HAUL DAY GATE
    // ─────────────────────────────────────────────────

    public function test_driver_can_accept_future_job_but_cannot_start_it_yet(): void
    {
        $logistics = $this->createLogisticsUser();
        $driver = $this->createDriverOnTeam($logistics);
        $job = $this->createJob($logistics, $driver, now()->addDays(5)->toDateString());

        $this->assertFalse($job->isDueToday());

        $this->actingAs($driver)->post(route('driver.jobs.accept', $job))->assertSessionHas('success');
        $this->assertNotNull($job->fresh()->accepted_at);

        $this->actingAs($driver)->patch(route('driver.jobs.status', $job))->assertSessionHas('error');
        $this->assertEquals('confirmed', $job->fresh()->status->value);
    }

    public function test_driver_can_start_job_due_today(): void
    {
        $this->fakeWeatherApi();

        $logistics = $this->createLogisticsUser();
        $driver = $this->createDriverOnTeam($logistics);
        $job = $this->createJob($logistics, $driver, now()->toDateString());

        $this->assertTrue($job->isDueToday());

        $this->actingAs($driver)->post(route('driver.jobs.accept', $job))->assertSessionHas('success');
        $this->actingAs($driver)->patch(route('driver.jobs.status', $job))->assertSessionHas('success');
        $this->assertEquals('in_progress', $job->fresh()->status->value);
    }

    public function test_scheduled_at_uses_harvest_date_not_pickup_time_only(): void
    {
        $logistics = $this->createLogisticsUser();
        $driver = $this->createDriverOnTeam($logistics);
        $job = $this->createJob($logistics, $driver, now()->addDays(3)->toDateString());

        $this->assertEquals(
            now()->addDays(3)->toDateString(),
            $job->scheduledAt()->toDateString()
        );
    }

    public function test_dashboard_splits_today_and_upcoming(): void
    {
        $logistics = $this->createLogisticsUser();
        $driver = $this->createDriverOnTeam($logistics);

        $today = $this->createJob($logistics, $driver, now()->toDateString());
        $later = $this->createJob($logistics, $driver, now()->addDays(4)->toDateString());

        $this->actingAs($driver)
            ->get(route('driver.dashboard'))
            ->assertOk()
            ->assertViewHas('todayJobs', fn ($jobs) => $jobs->count() === 1 && $jobs->first()->id === $today->id)
            ->assertViewHas('upcomingJobs', fn ($jobs) => $jobs->count() === 1 && $jobs->first()->id === $later->id);
    }

    // ─────────────────────────────────────────────────
    // MORNING NUDGE
    // ─────────────────────────────────────────────────

    public function test_haul_day_nudge_sends_once_per_job(): void
    {
        $logistics = $this->createLogisticsUser();
        $driver = $this->createDriverOnTeam($logistics);
        $job = $this->createJob($logistics, $driver, now()->toDateString());

        $this->artisan('drivers:notify-today')->assertSuccessful();
        $this->artisan('drivers:notify-today')->assertSuccessful();

        $count = Notification::where('user_id', $driver->id)
            ->where('title', 'like', 'Haul Day:%')
            ->count();

        $this->assertEquals(1, $count);
        $this->assertNotNull($job->fresh()->driver_notified_at);
    }

    public function test_haul_day_nudge_skips_future_jobs(): void
    {
        $logistics = $this->createLogisticsUser();
        $driver = $this->createDriverOnTeam($logistics);
        $job = $this->createJob($logistics, $driver, now()->addDays(5)->toDateString());

        $this->artisan('drivers:notify-today')->assertSuccessful();

        $this->assertEquals(0, Notification::where('user_id', $driver->id)->count());
        $this->assertNull($job->fresh()->driver_notified_at);
    }

    // ─────────────────────────────────────────────────
    // ASSIGN / REASSIGN
    // ─────────────────────────────────────────────────

    public function test_logistics_can_assign_driver_to_orphan_job(): void
    {
        $logistics = $this->createLogisticsUser();
        $driver = $this->createDriverOnTeam($logistics, 'New Driver');
        $job = $this->createJob($logistics, null, now()->addDays(2)->toDateString());

        $this->assertNull($job->driver_id);

        $this->actingAs($logistics)
            ->post(route('pooling.assign-driver', $job), ['driver_id' => $driver->id])
            ->assertSessionHas('success');

        $fresh = $job->fresh();
        $this->assertEquals($driver->id, $fresh->driver_id);
        $this->assertNull($fresh->accepted_at);
        $this->assertEquals($driver->id, $fresh->truck->driver_id);

        $this->assertEquals(1, Notification::where('user_id', $driver->id)
            ->where('title', 'New Route Assigned')->count());
    }

    public function test_reassign_clears_acceptance_and_notifies_previous_driver(): void
    {
        $logistics = $this->createLogisticsUser();
        $old = $this->createDriverOnTeam($logistics, 'Old Driver');
        $new = $this->createDriverOnTeam($logistics, 'New Driver');
        $job = $this->createJob($logistics, $old, now()->addDays(2)->toDateString(), ['accepted_at' => now()]);

        $this->actingAs($logistics)
            ->post(route('pooling.assign-driver', $job), ['driver_id' => $new->id])
            ->assertSessionHas('success');

        $fresh = $job->fresh();
        $this->assertEquals($new->id, $fresh->driver_id);
        $this->assertNull($fresh->accepted_at);

        $this->assertEquals(1, Notification::where('user_id', $old->id)
            ->where('title', 'Route Reassigned')->count());
        $this->assertEquals(1, Notification::where('user_id', $new->id)
            ->where('title', 'New Route Assigned')->count());
    }

    public function test_driver_cannot_be_assigned_once_run_has_started(): void
    {
        $logistics = $this->createLogisticsUser();
        $driver = $this->createDriverOnTeam($logistics);
        $truck = $this->createTruck($logistics);

        $job = PoolingJob::factory()->inProgress()->create([
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
        ]);

        $this->actingAs($logistics)
            ->post(route('pooling.assign-driver', $job), ['driver_id' => $driver->id])
            ->assertSessionHas('error');

        $this->assertEquals('in_progress', $job->fresh()->status->value);
    }

    public function test_foreign_driver_cannot_be_assigned(): void
    {
        $logistics = $this->createLogisticsUser();
        $otherLogistics = $this->createLogisticsUser();
        $foreign = $this->createDriverOnTeam($otherLogistics, 'Foreign Driver');
        $job = $this->createJob($logistics, null, now()->addDays(2)->toDateString());

        $this->actingAs($logistics)
            ->post(route('pooling.assign-driver', $job), ['driver_id' => $foreign->id])
            ->assertSessionHas('error');

        $this->assertNull($job->fresh()->driver_id);
    }
}
