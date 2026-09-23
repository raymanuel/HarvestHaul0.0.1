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

    public function test_completing_last_stop_frees_the_truck(): void
    {
        Storage::fake('local');
        $coop = $this->cooperative();
        $coopAdmin = $this->coopAdmin($coop);
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 1);

        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stops[0], 'picked_up']), [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $this->assertDatabaseHas('trucks', ['id' => $truck->id, 'status' => 'available']);
        $this->assertDatabaseHas('haul_jobs', ['id' => $job->id, 'status' => HaulJob::STATUS_COMPLETED]);
    }

    public function test_explicit_complete_frees_the_truck(): void
    {
        $coop = $this->cooperative();
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 1);
        $stops[0]->update(['status' => HaulJobStop::STATUS_PICKED_UP]);

        $this->actingAs($driver)->post(route('delivery.trips.complete', $job));

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

    public function test_skipped_stop_request_goes_back_to_approved_not_completed(): void
    {
        $coop = $this->cooperative();
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 1);

        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stops[0], 'skipped']));

        $this->assertDatabaseHas('haul_requests', ['id' => $stops[0]->haul_request_id, 'status' => HaulRequest::STATUS_APPROVED]);
    }

    public function test_picked_up_stop_request_still_becomes_completed(): void
    {
        Storage::fake('local');
        $coop = $this->cooperative();
        [$job, $truck, $driver, $stops] = $this->scheduledJobWithStops($coop, 1);

        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stops[0], 'picked_up']), [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $this->assertDatabaseHas('haul_requests', ['id' => $stops[0]->haul_request_id, 'status' => HaulRequest::STATUS_COMPLETED]);
    }
}
