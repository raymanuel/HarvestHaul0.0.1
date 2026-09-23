<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\HaulRequest;
use App\Models\TrackingRecord;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Cooperative\ConsolidationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NearestDriverAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function coop(): Cooperative
    {
        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
    }

    public function test_drivers_are_sorted_nearest_first_when_a_coordinate_is_given(): void
    {
        $coop = $this->coop();
        $near = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id, 'name' => 'Near Driver']);
        $far = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id, 'name' => 'Far Driver']);

        // Target point: 6.1000, 125.1000
        TrackingRecord::create(['job_type' => 'App\\Models\\HaulJob', 'job_id' => 1, 'driver_id' => $near->id, 'latitude' => 6.1010, 'longitude' => 125.1010, 'posted_at' => now()]);
        TrackingRecord::create(['job_type' => 'App\\Models\\HaulJob', 'job_id' => 1, 'driver_id' => $far->id, 'latitude' => 7.5000, 'longitude' => 126.5000, 'posted_at' => now()]);

        $engine = app(ConsolidationEngine::class);
        $drivers = $engine->availableDrivers($coop, today()->toDateString(), null, ['lat' => 6.1000, 'lng' => 125.1000]);

        $this->assertEquals(['Near Driver', 'Far Driver'], $drivers->pluck('name')->all());
    }

    public function test_drivers_with_no_position_sort_after_drivers_with_a_known_position(): void
    {
        $coop = $this->coop();
        $tracked = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id, 'name' => 'Tracked Driver']);
        $untracked = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id, 'name' => 'Untracked Driver']);

        TrackingRecord::create(['job_type' => 'App\\Models\\HaulJob', 'job_id' => 1, 'driver_id' => $tracked->id, 'latitude' => 20.0, 'longitude' => 20.0, 'posted_at' => now()]);

        $engine = app(ConsolidationEngine::class);
        $drivers = $engine->availableDrivers($coop, today()->toDateString(), null, ['lat' => 6.1000, 'lng' => 125.1000]);

        $this->assertEquals(['Tracked Driver', 'Untracked Driver'], $drivers->pluck('name')->all());
    }

    public function test_a_position_older_than_the_configured_max_age_is_ignored(): void
    {
        $coop = $this->coop();
        $stale = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id, 'name' => 'Stale Driver']);
        $fresh = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id, 'name' => 'Fresh Driver']);

        // Stale driver is geographically closer but the ping is 30 days old.
        TrackingRecord::create(['job_type' => 'App\\Models\\HaulJob', 'job_id' => 1, 'driver_id' => $stale->id, 'latitude' => 6.1001, 'longitude' => 125.1001, 'posted_at' => now()->subDays(30)]);
        TrackingRecord::create(['job_type' => 'App\\Models\\HaulJob', 'job_id' => 1, 'driver_id' => $fresh->id, 'latitude' => 6.2000, 'longitude' => 125.2000, 'posted_at' => now()->subDay()]);

        $engine = app(ConsolidationEngine::class);
        $drivers = $engine->availableDrivers($coop, today()->toDateString(), null, ['lat' => 6.1000, 'lng' => 125.1000]);

        // Fresh (within 7-day default) sorts first even though further away;
        // stale is treated as no-position and pushed to the back.
        $this->assertEquals(['Fresh Driver', 'Stale Driver'], $drivers->pluck('name')->all());
    }

    public function test_no_nearto_given_keeps_the_original_alphabetical_order(): void
    {
        $coop = $this->coop();
        User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id, 'name' => 'Zed Driver']);
        User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id, 'name' => 'Ann Driver']);

        $engine = app(ConsolidationEngine::class);
        $drivers = $engine->availableDrivers($coop, today()->toDateString());

        $this->assertEquals(['Ann Driver', 'Zed Driver'], $drivers->pluck('name')->all());
    }

    public function test_pickup_trip_show_page_still_renders_with_the_new_parameter(): void
    {
        $coop = $this->coop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $haulRequest = HaulRequest::factory()->create(['farmer_id' => $farmer->id, 'cooperative_id' => $coop->id]);
        $haulJob = HaulJob::factory()->create(['cooperative_id' => $coop->id, 'haul_request_id' => $haulRequest->id, 'status' => HaulJob::STATUS_SCHEDULED]);
        \App\Models\HaulJobStop::factory()->create(['haul_job_id' => $haulJob->id, 'haul_request_id' => $haulRequest->id]);

        $response = $this->actingAs($admin)->get(route('coop.pickups.show', $haulJob));

        $response->assertOk();
    }
}
