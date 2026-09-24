<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\Notification;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PickupTripLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function fakeOsrm(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response([
                'code' => 'Ok',
                'durations' => array_fill(0, 5, array_fill(0, 5, 300)),
                'distances' => array_fill(0, 5, array_fill(0, 5, 5000)),
            ]),
            'router.project-osrm.org/route/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 10000,
                    'duration' => 900,
                    'geometry' => ['coordinates' => [[125.1716, 6.1164], [125.18, 6.12], [125.1716, 6.1164]]],
                ]],
            ]),
        ]);
    }

    private function cooperative(): Cooperative
    {
        return Cooperative::create([
            'name'           => 'GenSan AgCoop',
            'type'           => 'primary',
            'contact_number' => '09171234567',
            'official_email' => 'coop@example.com',
            'status'         => Cooperative::STATUS_APPROVED,
            'latitude'       => '6.1164',
            'longitude'      => '125.1716',
        ]);
    }

    private function coopAdmin(Cooperative $cooperative): User
    {
        return User::factory()->create([
            'role'              => UserRole::COOP_ADMIN->value,
            'cooperative_id'    => $cooperative->id,
            'email_verified_at' => now(),
        ]);
    }

    private function scheduledJobWithStops(Cooperative $cooperative, int $stopCount = 2): array
    {
        $this->fakeOsrm();

        $truck = Truck::factory()->create(['cooperative_id' => $cooperative->id, 'status' => 'in_use', 'capacity_kg' => 5000]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $cooperative->id]);

        $job = HaulJob::create([
            'haul_request_id' => null,
            'cooperative_id' => $cooperative->id,
            'delivery_personnel_id' => $driver->id,
            'truck_id' => $truck->id,
            'pickup_date' => today()->addDay(),
            'status' => HaulJob::STATUS_SCHEDULED,
            'route_distance_km' => 10.0,
        ]);

        $stops = [];
        for ($i = 1; $i <= $stopCount; $i++) {
            $req = HaulRequest::factory()->create([
                'cooperative_id' => $cooperative->id,
                'status' => HaulRequest::STATUS_SCHEDULED,
                'preferred_pickup_date' => $job->pickup_date,
                'pickup_location_lat' => 6.12,
                'pickup_location_lng' => 125.18,
            ]);
            $stops[] = HaulJobStop::create([
                'haul_job_id' => $job->id,
                'haul_request_id' => $req->id,
                'sequence_no' => $i,
                'status' => HaulJobStop::STATUS_PENDING,
                'planned_arrival_at' => $job->pickup_date->copy()->addMinutes(10 * $i),
            ]);
        }

        return [$job, $truck, $driver, $stops];
    }

    public function test_picked_up_last_stop_does_not_free_the_truck_until_delivered_to_depot(): void
    {
        Storage::fake('local');
        $coop = $this->cooperative();
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 1);

        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stops[0], 'picked_up']), [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        // Crop collected, but the trip isn't done — it still has to reach
        // the co-op (the shared destination for every pickup on the trip).
        $this->assertDatabaseHas('trucks', ['id' => $truck->id, 'status' => 'in_use']);
        $job->refresh();
        $this->assertNotEquals(HaulJob::STATUS_COMPLETED, $job->status);

        $this->actingAs($driver)->post(route('delivery.trips.complete', $job), [
            'photo' => UploadedFile::fake()->image('depot.jpg'),
        ]);

        $this->assertDatabaseHas('trucks', ['id' => $truck->id, 'status' => 'available']);
        $this->assertDatabaseHas('haul_jobs', ['id' => $job->id, 'status' => HaulJob::STATUS_COMPLETED]);
        $job->refresh();
        $this->assertNotNull($job->depot_delivered_at);
        $this->assertNotNull($job->depot_pod_photo_path);
    }

    public function test_explicit_complete_requires_a_depot_photo_and_frees_the_truck(): void
    {
        Storage::fake('local');
        $coop = $this->cooperative();
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 1);
        $stops[0]->update(['status' => HaulJobStop::STATUS_PICKED_UP]);

        $noPhoto = $this->actingAs($driver)->post(route('delivery.trips.complete', $job));
        $noPhoto->assertSessionHasErrors('photo');

        $this->actingAs($driver)->post(route('delivery.trips.complete', $job), [
            'photo' => UploadedFile::fake()->image('depot.jpg'),
        ]);

        $this->assertDatabaseHas('trucks', ['id' => $truck->id, 'status' => 'available']);
    }

    public function test_remove_stop_recalculates_remaining_schedule_and_route(): void
    {
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 2);

        $originalArrival = $stops[1]->planned_arrival_at;

        $this->actingAs($coopAdmin)->delete(route('coop.pickups.stops.remove', $stops[0]));

        $stops[1]->refresh();
        $job->refresh();

        $this->assertNotNull($stops[1]->planned_arrival_at);
        $this->assertNotEquals($originalArrival->toDateTimeString(), $stops[1]->planned_arrival_at->toDateTimeString());
        $this->assertEquals(10.0, (float) $job->route_distance_km);
        $this->assertEquals(HaulJob::STATUS_SCHEDULED, $job->status);
    }

    public function test_remove_last_stop_cancels_trip_and_frees_truck(): void
    {
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 1);

        $this->actingAs($coopAdmin)->delete(route('coop.pickups.stops.remove', $stops[0]));

        $job->refresh();
        $this->assertEquals(HaulJob::STATUS_CANCELLED, $job->status);
        $this->assertDatabaseHas('trucks', ['id' => $truck->id, 'status' => 'available']);
        $this->assertDatabaseHas('haul_requests', ['id' => $stops[0]->haul_request_id, 'status' => HaulRequest::STATUS_APPROVED]);
    }

    public function test_cancel_reverts_all_stops_and_frees_truck(): void
    {
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 2);

        $response = $this->actingAs($coopAdmin)->post(route('coop.pickups.cancel', $job));

        $response->assertRedirect();
        $job->refresh();
        $this->assertEquals(HaulJob::STATUS_CANCELLED, $job->status);
        $this->assertDatabaseHas('trucks', ['id' => $truck->id, 'status' => 'available']);
        foreach ($stops as $stop) {
            $this->assertDatabaseHas('haul_requests', ['id' => $stop->haul_request_id, 'status' => HaulRequest::STATUS_APPROVED]);
        }
    }

    public function test_coop_admin_cannot_cancel_another_cooperatives_trip(): void
    {
        $coopA = $this->cooperative();
        $coopB = $this->cooperative();
        $coopAdminA = $this->coopAdmin($coopA);
        [$job] = $this->scheduledJobWithStops($coopB, 1);

        $response = $this->actingAs($coopAdminA)->post(route('coop.pickups.cancel', $job));

        $response->assertForbidden();
    }

    public function test_marking_stop_failed_requires_reason_and_notifies_coop_admin_without_touching_request(): void
    {
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 1);

        $noReason = $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stops[0], 'failed']));
        $noReason->assertSessionHasErrors('reason');

        $response = $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stops[0], 'failed']), [
            'reason' => 'Farmer not reachable at the pickup location.',
        ]);

        $response->assertRedirect();
        $stops[0]->refresh();
        $this->assertEquals(HaulJobStop::STATUS_FAILED, $stops[0]->status);
        $this->assertEquals('Farmer not reachable at the pickup location.', $stops[0]->failure_reason);

        // Single-stop trip: marking it failed closes out the trip immediately.
        // A failed pickup never happened, so the request goes back to approved
        // for replanning — never "completed" (that fix was a bug this test caught).
        $this->assertDatabaseHas('haul_requests', ['id' => $stops[0]->haul_request_id, 'status' => HaulRequest::STATUS_APPROVED]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $coopAdmin->id,
            'title' => 'Pickup problem reported',
        ]);
    }

    public function test_skipping_a_pickup_stop_is_no_longer_allowed(): void
    {
        $coop = $this->cooperative();
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 1);

        $response = $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stops[0], 'skipped']));

        $response->assertSessionHasErrors('stop');
        $stops[0]->refresh();
        $this->assertEquals(HaulJobStop::STATUS_PENDING, $stops[0]->status);
    }

    public function test_picked_up_stop_request_becomes_completed_once_delivered_to_depot(): void
    {
        Storage::fake('local');
        $coop = $this->cooperative();
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 1);

        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stops[0], 'picked_up']), [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        $this->actingAs($driver)->post(route('delivery.trips.complete', $job), [
            'photo' => UploadedFile::fake()->image('depot.jpg'),
        ]);

        $this->assertDatabaseHas('haul_requests', ['id' => $stops[0]->haul_request_id, 'status' => HaulRequest::STATUS_COMPLETED]);
    }

    public function test_farmer_is_notified_when_the_truck_arrives_and_when_picked_up(): void
    {
        Storage::fake('local');
        $coop = $this->cooperative();
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 1);
        $farmerId = $stops[0]->haulRequest->farmer_id;

        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stops[0], 'arrived']));
        $this->assertDatabaseHas('notifications', ['user_id' => $farmerId, 'title' => 'Truck has arrived']);

        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stops[0], 'picked_up']), [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        $this->assertDatabaseHas('notifications', ['user_id' => $farmerId, 'title' => 'Crop picked up']);
    }

    public function test_driver_near_next_stop_notifies_that_farmer_once(): void
    {
        $coop = $this->cooperative();
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 1);
        $stops[0]->haulRequest->update(['pickup_location_lat' => 6.1200, 'pickup_location_lng' => 125.1800]);
        $farmerId = $stops[0]->haulRequest->farmer_id;

        $this->actingAs($driver)->postJson(route('delivery.trips.location', $job), [
            'latitude' => 6.1200, 'longitude' => 125.1800,
        ])->assertOk();

        $this->assertEquals(1, Notification::where('user_id', $farmerId)->where('title', 'Truck is nearby')->count());
        $stops[0]->refresh();
        $this->assertNotNull($stops[0]->proximity_notified_at);

        // Same stop, another ping close by — no second alert.
        $this->actingAs($driver)->postJson(route('delivery.trips.location', $job), [
            'latitude' => 6.1201, 'longitude' => 125.1801,
        ])->assertOk();

        $this->assertEquals(1, Notification::where('user_id', $farmerId)->where('title', 'Truck is nearby')->count());
    }

    public function test_driver_far_from_next_stop_does_not_notify(): void
    {
        $coop = $this->cooperative();
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 1);
        $stops[0]->haulRequest->update(['pickup_location_lat' => 6.1200, 'pickup_location_lng' => 125.1800]);
        $farmerId = $stops[0]->haulRequest->farmer_id;

        $this->actingAs($driver)->postJson(route('delivery.trips.location', $job), [
            'latitude' => 7.5, 'longitude' => 126.5,
        ])->assertOk();

        $this->assertDatabaseMissing('notifications', ['user_id' => $farmerId, 'title' => 'Truck is nearby']);
        $stops[0]->refresh();
        $this->assertNull($stops[0]->proximity_notified_at);
    }

    public function test_proximity_alert_moves_to_the_next_farmer_once_the_first_stop_closes(): void
    {
        Storage::fake('local');
        $coop = $this->cooperative();
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 2);
        $stops[0]->haulRequest->update(['pickup_location_lat' => 6.1200, 'pickup_location_lng' => 125.1800]);
        $stops[1]->haulRequest->update(['pickup_location_lat' => 6.2000, 'pickup_location_lng' => 125.2500]);
        $secondFarmerId = $stops[1]->haulRequest->farmer_id;

        // Near stop 1 first.
        $this->actingAs($driver)->postJson(route('delivery.trips.location', $job), [
            'latitude' => 6.1200, 'longitude' => 125.1800,
        ]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $secondFarmerId, 'title' => 'Truck is nearby']);

        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stops[0], 'picked_up']), [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        // Now near stop 2 — the second farmer gets their own alert.
        $this->actingAs($driver)->postJson(route('delivery.trips.location', $job), [
            'latitude' => 6.2000, 'longitude' => 125.2500,
        ]);
        $this->assertDatabaseHas('notifications', ['user_id' => $secondFarmerId, 'title' => 'Truck is nearby']);
    }

    public function test_farmer_track_page_shows_every_stop_on_the_trip(): void
    {
        $coop = $this->cooperative();
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 2);
        $stops[0]->haulRequest->farmer->update(['name' => 'Farmer One']);
        $stops[1]->haulRequest->farmer->update(['name' => 'Farmer Two']);

        $response = $this->actingAs($stops[0]->haulRequest->farmer)->get(route('farmer.haul-requests.track', $stops[0]->haulRequest));

        $response->assertOk();
        $response->assertSee('Farmer One');
        $response->assertSee('Farmer Two');
    }
}
