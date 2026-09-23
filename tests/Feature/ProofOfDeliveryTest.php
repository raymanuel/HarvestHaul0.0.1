<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProofOfDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function pickupSetup(): array
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $haulRequest = HaulRequest::factory()->create(['farmer_id' => $farmer->id, 'cooperative_id' => $coop->id]);
        $haulJob = HaulJob::factory()->create([
            'cooperative_id' => $coop->id, 'haul_request_id' => $haulRequest->id,
            'delivery_personnel_id' => $driver->id, 'status' => HaulJob::STATUS_SCHEDULED,
        ]);
        $stop = HaulJobStop::factory()->create([
            'haul_job_id' => $haulJob->id, 'haul_request_id' => $haulRequest->id,
            'status' => HaulJobStop::STATUS_ARRIVED,
        ]);

        return [$coop, $driver, $stop];
    }

    public function test_marking_picked_up_without_a_photo_is_rejected(): void
    {
        [, $driver, $stop] = $this->pickupSetup();

        $response = $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stop, 'picked_up']));

        $response->assertSessionHasErrors(['photo']);
        $this->assertEquals(HaulJobStop::STATUS_ARRIVED, $stop->refresh()->status);
    }

    public function test_marking_picked_up_with_a_photo_succeeds_and_stores_it(): void
    {
        Storage::fake('local');
        [, $driver, $stop] = $this->pickupSetup();

        $response = $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stop, 'picked_up']), [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response->assertRedirect();
        $stop->refresh();
        $this->assertEquals(HaulJobStop::STATUS_PICKED_UP, $stop->status);
        $this->assertNotNull($stop->pod_photo_path);
        Storage::disk('local')->assertExists($stop->pod_photo_path);
    }

    public function test_marking_arrived_does_not_require_a_photo(): void
    {
        [, $driver, $stop] = $this->pickupSetup();
        $stop->update(['status' => HaulJobStop::STATUS_PENDING]);

        $response = $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stop, 'arrived']));

        $response->assertRedirect();
        $this->assertEquals(HaulJobStop::STATUS_ARRIVED, $stop->refresh()->status);
    }

    public function test_reporting_a_problem_does_not_require_a_photo(): void
    {
        [, $driver, $stop] = $this->pickupSetup();

        $response = $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stop, 'failed']), [
            'reason' => 'Farmer not reachable.',
        ]);

        $response->assertRedirect();
        $this->assertEquals(HaulJobStop::STATUS_FAILED, $stop->refresh()->status);
    }

    public function test_the_assigned_driver_can_view_their_own_pod_photo(): void
    {
        Storage::fake('local');
        [, $driver, $stop] = $this->pickupSetup();
        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stop, 'picked_up']), [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response = $this->actingAs($driver)->get(route('files.show', ['type' => 'pod-photo', 'id' => $stop->refresh()->id]));

        $response->assertOk();
    }

    public function test_the_coop_admin_of_that_cooperative_can_view_the_pod_photo(): void
    {
        Storage::fake('local');
        [$coop, $driver, $stop] = $this->pickupSetup();
        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stop, 'picked_up']), [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);

        $response = $this->actingAs($admin)->get(route('files.show', ['type' => 'pod-photo', 'id' => $stop->refresh()->id]));

        $response->assertOk();
    }

    public function test_a_different_cooperatives_admin_cannot_view_the_pod_photo(): void
    {
        Storage::fake('local');
        [, $driver, $stop] = $this->pickupSetup();
        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stop, 'picked_up']), [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        $otherCoop = Cooperative::create([
            'name' => 'Other Coop', 'type' => 'primary',
            'contact_number' => '09170000000', 'official_email' => 'other@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $otherAdmin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $otherCoop->id]);

        $response = $this->actingAs($otherAdmin)->get(route('files.show', ['type' => 'pod-photo', 'id' => $stop->refresh()->id]));

        $response->assertForbidden();
    }

    public function test_an_unrelated_driver_cannot_view_someone_elses_pod_photo(): void
    {
        Storage::fake('local');
        [$coop, $driver, $stop] = $this->pickupSetup();
        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stop, 'picked_up']), [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        $otherDriver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);

        $response = $this->actingAs($otherDriver)->get(route('files.show', ['type' => 'pod-photo', 'id' => $stop->refresh()->id]));

        $response->assertForbidden();
    }
}
