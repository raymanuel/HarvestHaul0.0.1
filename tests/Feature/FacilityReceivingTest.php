<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\CropGrade;
use App\Models\HaulJob;
use App\Models\HaulRequest;
use App\Models\ReceivingRecord;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityReceivingTest extends TestCase
{
    use RefreshDatabase;

    private function cooperative(string $name = 'GenSan AgCoop'): Cooperative
    {
        return Cooperative::create([
            'name' => $name, 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => strtolower(str_replace(' ', '', $name)).'@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
    }

    private function completedTripRecord(Cooperative $coop, array $overrides = []): ReceivingRecord
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id]);
        $grade = CropGrade::factory()->create();
        $req = HaulRequest::factory()->create(['cooperative_id' => $coop->id, 'status' => HaulRequest::STATUS_COMPLETED]);
        $job = HaulJob::create([
            'haul_request_id' => null, 'cooperative_id' => $coop->id,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $truck->id,
            'pickup_date' => today(), 'status' => HaulJob::STATUS_COMPLETED,
        ]);

        return ReceivingRecord::create(array_merge([
            'haul_job_id' => $job->id, 'haul_request_id' => $req->id, 'cooperative_id' => $coop->id,
            'farmer_id' => $farmer->id, 'crop_id' => $req->crop_id, 'crop_grade_id' => $grade->id,
            'actual_sacks' => 78, 'actual_weight_kg' => 3920,
            'recorded_by' => $driver->id, 'status' => ReceivingRecord::STATUS_PENDING,
        ], $overrides));
    }

    private function coopAdmin(Cooperative $coop): User
    {
        return User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id, 'email_verified_at' => now()]);
    }

    public function test_matching_weight_verifies_without_variance(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->completedTripRecord($coop);

        $response = $this->actingAs($admin)->post(route('coop.facility-receiving.verify', $record), [
            'facility_received_weight_kg' => 3920,
        ]);

        $response->assertRedirect();
        $record->refresh();
        $this->assertNotNull($record->facility_verified_at);
        $this->assertEquals(0.0, (float) $record->variance_kg);
        $this->assertNull($record->variance_status);
    }

    public function test_mismatched_weight_requires_notes_and_flags(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->completedTripRecord($coop);

        $blocked = $this->actingAs($admin)->post(route('coop.facility-receiving.verify', $record), [
            'facility_received_weight_kg' => 3900,
        ]);
        $blocked->assertSessionHasErrors('variance_notes');
        $this->assertNull($record->fresh()->facility_verified_at);

        $response = $this->actingAs($admin)->post(route('coop.facility-receiving.verify', $record), [
            'facility_received_weight_kg' => 3900,
            'variance_notes' => 'Moisture loss during transit.',
        ]);
        $response->assertRedirect();

        $record->refresh();
        $this->assertEquals(-20.0, (float) $record->variance_kg);
        $this->assertEquals('flagged', $record->variance_status);
    }

    public function test_cannot_verify_twice(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->completedTripRecord($coop, [
            'facility_received_weight_kg' => 3920, 'facility_verified_at' => now(), 'facility_verified_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('coop.facility-receiving.verify', $record), [
            'facility_received_weight_kg' => 3920,
        ]);

        $response->assertSessionHasErrors('receiving');
    }

    public function test_resolve_clears_flagged_variance(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->completedTripRecord($coop, [
            'facility_received_weight_kg' => 3900, 'facility_verified_at' => now(), 'facility_verified_by' => $admin->id,
            'variance_kg' => -20, 'variance_status' => 'flagged', 'variance_notes' => 'Moisture loss.',
        ]);

        $response = $this->actingAs($admin)->post(route('coop.facility-receiving.resolve', $record), [
            'variance_notes' => 'Confirmed with farmer, accepted as normal drying loss.',
        ]);

        $response->assertRedirect();
        $this->assertEquals('resolved', $record->fresh()->variance_status);
    }

    public function test_cannot_resolve_a_non_flagged_record(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->completedTripRecord($coop, [
            'facility_received_weight_kg' => 3920, 'facility_verified_at' => now(), 'facility_verified_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('coop.facility-receiving.resolve', $record), [
            'variance_notes' => 'n/a',
        ]);

        $response->assertSessionHasErrors('receiving');
    }

    public function test_other_cooperative_cannot_verify(): void
    {
        $coop = $this->cooperative('GenSan AgCoop');
        $otherCoop = $this->cooperative('Davao AgCoop');
        $otherAdmin = $this->coopAdmin($otherCoop);
        $record = $this->completedTripRecord($coop);

        $response = $this->actingAs($otherAdmin)->post(route('coop.facility-receiving.verify', $record), [
            'facility_received_weight_kg' => 3920,
        ]);

        $response->assertForbidden();
    }
}
