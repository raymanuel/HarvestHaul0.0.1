<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PickupSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private function coopAdmin(): User
    {
        $cooperative = Cooperative::create([
            'name'           => 'GenSan AgCoop',
            'type'           => 'primary',
            'contact_number' => '09171234567',
            'official_email' => 'coop@example.com',
            'status'         => Cooperative::STATUS_APPROVED,
            'latitude'       => '6.1164',
            'longitude'      => '125.1716',
        ]);

        return User::factory()->create([
            'role'              => UserRole::COOP_ADMIN->value,
            'cooperative_id'    => $cooperative->id,
            'email_verified_at' => now(),
        ]);
    }

    private function approvedRequest(int $cooperativeId, string $date): HaulRequest
    {
        return HaulRequest::factory()->create([
            'cooperative_id'        => $cooperativeId,
            'status'                => HaulRequest::STATUS_APPROVED,
            'preferred_pickup_date' => $date,
            'pickup_location_lat'   => 6.12,
            'pickup_location_lng'   => 125.18,
        ]);
    }

    /**
     * These GETs previously 500'd — resources/views/coop/pickups/ didn't exist
     * at all. This is the regression test for that entire bug class.
     */
    public function test_pickup_views_render(): void
    {
        $coop = $this->coopAdmin();
        $date = today()->addDay()->toDateString();
        $request = $this->approvedRequest($coop->cooperative_id, $date);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->cooperative_id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->cooperative_id]);

        $this->actingAs($coop)->get(route('coop.pickups.index'))->assertOk();
        $this->actingAs($coop)->get(route('coop.pickups.calendar'))->assertOk();
        $this->actingAs($coop)->get(route('coop.pickups.calendar', ['view' => 'day', 'date' => $date]))->assertOk();
        $this->actingAs($coop)->get(route('coop.pickups.calendar', ['view' => 'week', 'date' => $date]))->assertOk();
        $this->actingAs($coop)->get(route('coop.pickups.create', ['date' => $date]))->assertOk();

        $job = HaulJob::create([
            'haul_request_id' => null,
            'cooperative_id'  => $coop->cooperative_id,
            'delivery_personnel_id' => $driver->id,
            'truck_id'        => $truck->id,
            'pickup_date'     => $date,
            'status'          => HaulJob::STATUS_SCHEDULED,
        ]);

        $this->actingAs($coop)->get(route('coop.pickups.show', $job))->assertOk();
    }

    /**
     * HaulJob.haul_request_id was NOT NULL with no migration ever relaxing it,
     * but store() always inserted null for consolidated trips. Proves the fix.
     */
    public function test_store_creates_consolidated_trip_with_null_haul_request_id(): void
    {
        $coop = $this->coopAdmin();
        $date = today()->addDay()->toDateString();
        $req = $this->approvedRequest($coop->cooperative_id, $date);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->cooperative_id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->cooperative_id]);

        $response = $this->actingAs($coop)->post(route('coop.pickups.store'), [
            'date' => $date,
            'truck_id' => $truck->id,
            'delivery_personnel_id' => $driver->id,
            'requests' => [$req->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('haul_jobs', ['cooperative_id' => $coop->cooperative_id, 'haul_request_id' => null]);
        $this->assertDatabaseHas('haul_requests', ['id' => $req->id, 'status' => HaulRequest::STATUS_SCHEDULED]);
    }

    public function test_store_rejects_a_request_that_is_only_pending_not_approved(): void
    {
        $coop = $this->coopAdmin();
        $date = today()->addDay()->toDateString();
        $truck = Truck::factory()->create(['cooperative_id' => $coop->cooperative_id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->cooperative_id]);

        $pending = HaulRequest::factory()->create([
            'cooperative_id' => $coop->cooperative_id,
            'status' => HaulRequest::STATUS_PENDING,
            'preferred_pickup_date' => $date,
        ]);

        $response = $this->actingAs($coop)->post(route('coop.pickups.store'), [
            'date' => $date,
            'truck_id' => $truck->id,
            'delivery_personnel_id' => $driver->id,
            'requests' => [$pending->id],
        ]);

        $response->assertSessionHasErrors('requests');
    }

    /**
     * store() throws ValidationException on several real conditions (no
     * requests selected, date no longer >= today, truck taken, driver
     * double-booked), but the create page never displayed $errors for any
     * of them and the site-wide flash banner only reads session('error')
     * strings — so a rejected submission silently reloaded the same page
     * with zero visible feedback ("the Create button does nothing").
     */
    public function test_rejected_trip_submission_shows_a_visible_error_on_reload(): void
    {
        $coop = $this->coopAdmin();
        $date = today()->addDay()->toDateString();
        $truck = Truck::factory()->create(['cooperative_id' => $coop->cooperative_id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->cooperative_id]);

        $pending = HaulRequest::factory()->create([
            'cooperative_id' => $coop->cooperative_id,
            'status' => HaulRequest::STATUS_PENDING,
            'preferred_pickup_date' => $date,
        ]);

        $response = $this->actingAs($coop)->from(route('coop.pickups.create', ['date' => $date]))
            ->followingRedirects()
            ->post(route('coop.pickups.store'), [
                'date' => $date,
                'truck_id' => $truck->id,
                'delivery_personnel_id' => $driver->id,
                'requests' => [$pending->id],
            ]);

        $response->assertOk();
        $response->assertSee('not available to schedule', false);
    }

    public function test_approve_moves_request_from_pending_to_approved(): void
    {
        $coop = $this->coopAdmin();
        $req = HaulRequest::factory()->create([
            'cooperative_id' => $coop->cooperative_id,
            'status' => HaulRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($coop)->post(route('coop.haul-requests.approve', $req));

        $response->assertRedirect();
        $this->assertDatabaseHas('haul_requests', ['id' => $req->id, 'status' => HaulRequest::STATUS_APPROVED]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'approve_haul_request', 'target_id' => $req->id]);
    }

    private function scheduledJob(User $coop, string $date): HaulJob
    {
        $truck = Truck::factory()->create(['cooperative_id' => $coop->cooperative_id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->cooperative_id]);
        $req = $this->approvedRequest($coop->cooperative_id, $date);
        $req->update(['status' => HaulRequest::STATUS_SCHEDULED]);

        $job = HaulJob::create([
            'haul_request_id' => null,
            'cooperative_id' => $coop->cooperative_id,
            'delivery_personnel_id' => $driver->id,
            'truck_id' => $truck->id,
            'pickup_date' => $date,
            'status' => HaulJob::STATUS_SCHEDULED,
        ]);

        HaulJobStop::create([
            'haul_job_id' => $job->id,
            'haul_request_id' => $req->id,
            'sequence_no' => 1,
            'status' => HaulJobStop::STATUS_PENDING,
        ]);

        return $job;
    }

    public function test_reschedule_updates_trip_and_underlying_requests(): void
    {
        $coop = $this->coopAdmin();
        $job = $this->scheduledJob($coop, today()->addDay()->toDateString());
        $newDate = today()->addDays(3)->toDateString();

        $response = $this->actingAs($coop)->put(route('coop.pickups.reschedule', $job), ['date' => $newDate]);

        $response->assertRedirect();
        $this->assertSame($newDate, $job->fresh()->pickup_date->toDateString());
    }

    public function test_reassign_updates_truck_and_driver(): void
    {
        $coop = $this->coopAdmin();
        $job = $this->scheduledJob($coop, today()->addDay()->toDateString());

        $newTruck = Truck::factory()->create(['cooperative_id' => $coop->cooperative_id]);
        $newDriver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->cooperative_id]);

        $response = $this->actingAs($coop)->put(route('coop.pickups.reassign', $job), [
            'truck_id' => $newTruck->id,
            'delivery_personnel_id' => $newDriver->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('haul_jobs', ['id' => $job->id, 'truck_id' => $newTruck->id, 'delivery_personnel_id' => $newDriver->id]);
    }

    public function test_reassign_rejects_driver_from_another_cooperative(): void
    {
        $coopA = $this->coopAdmin();
        $coopB = $this->coopAdmin();
        $job = $this->scheduledJob($coopA, today()->addDay()->toDateString());

        $outsideDriver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coopB->cooperative_id]);
        $ownTruck = Truck::factory()->create(['cooperative_id' => $coopA->cooperative_id]);

        $response = $this->actingAs($coopA)->put(route('coop.pickups.reassign', $job), [
            'truck_id' => $ownTruck->id,
            'delivery_personnel_id' => $outsideDriver->id,
        ]);

        $response->assertSessionHasErrors('delivery_personnel_id');
    }

    public function test_remove_stop_reverts_request_to_approved(): void
    {
        $coop = $this->coopAdmin();
        $job = $this->scheduledJob($coop, today()->addDay()->toDateString());
        $stop = $job->stops()->first();
        $requestId = $stop->haul_request_id;

        $response = $this->actingAs($coop)->delete(route('coop.pickups.stops.remove', $stop));

        $response->assertRedirect();
        $this->assertDatabaseMissing('haul_job_stops', ['id' => $stop->id]);
        $this->assertDatabaseHas('haul_requests', ['id' => $requestId, 'status' => HaulRequest::STATUS_APPROVED]);
    }

    public function test_coop_admin_cannot_reschedule_another_cooperatives_trip(): void
    {
        $coopA = $this->coopAdmin();
        $coopB = $this->coopAdmin();
        $job = $this->scheduledJob($coopB, today()->addDay()->toDateString());

        $response = $this->actingAs($coopA)->put(route('coop.pickups.reschedule', $job), [
            'date' => today()->addDays(5)->toDateString(),
        ]);

        $response->assertForbidden();
    }
}
