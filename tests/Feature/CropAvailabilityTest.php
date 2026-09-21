<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\CropAvailability;
use App\Models\CropGrade;
use App\Models\HaulJob;
use App\Models\HaulRequest;
use App\Models\ReceivingRecord;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CropAvailabilityTest extends TestCase
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

    private function confirmedListing(Cooperative $coop): CropAvailability
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
        $record = ReceivingRecord::create([
            'haul_job_id' => $job->id, 'haul_request_id' => $req->id, 'cooperative_id' => $coop->id,
            'farmer_id' => $farmer->id, 'crop_id' => $req->crop_id, 'crop_grade_id' => $grade->id,
            'actual_sacks' => 78, 'actual_weight_kg' => 3920,
            'recorded_by' => $driver->id, 'status' => ReceivingRecord::STATUS_CONFIRMED,
            'buying_price_per_kg' => 18, 'total_amount' => 70560,
        ]);

        return CropAvailability::create([
            'cooperative_id' => $coop->id, 'receiving_record_id' => $record->id,
            'crop_id' => $record->crop_id, 'crop_grade_id' => $record->crop_grade_id,
            'quantity_kg' => 3920, 'status' => CropAvailability::STATUS_AVAILABLE,
        ]);
    }

    private function coopAdmin(Cooperative $coop): User
    {
        return User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id, 'email_verified_at' => now()]);
    }

    public function test_coop_admin_can_list_availability(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $this->confirmedListing($coop);

        $this->actingAs($admin)->get(route('coop.availability.index'))->assertOk();
    }

    public function test_coop_admin_can_set_selling_price(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $listing = $this->confirmedListing($coop);

        $response = $this->actingAs($admin)->post(route('coop.availability.price', $listing), [
            'selling_price_per_kg' => 25,
        ]);

        $response->assertRedirect();
        $this->assertEquals(25.00, (float) $listing->fresh()->selling_price_per_kg);
    }

    public function test_other_cooperative_cannot_set_price(): void
    {
        $coop = $this->cooperative('GenSan AgCoop');
        $otherCoop = $this->cooperative('Davao AgCoop');
        $otherAdmin = $this->coopAdmin($otherCoop);
        $listing = $this->confirmedListing($coop);

        $response = $this->actingAs($otherAdmin)->post(route('coop.availability.price', $listing), [
            'selling_price_per_kg' => 25,
        ]);

        $response->assertForbidden();
        $this->assertNull($listing->fresh()->selling_price_per_kg);
    }

    public function test_archive_and_restore_toggle_status(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $listing = $this->confirmedListing($coop);

        $this->actingAs($admin)->post(route('coop.availability.archive', $listing))->assertRedirect();
        $this->assertEquals(CropAvailability::STATUS_ARCHIVED, $listing->fresh()->status);

        $this->actingAs($admin)->post(route('coop.availability.restore', $listing))->assertRedirect();
        $this->assertEquals(CropAvailability::STATUS_AVAILABLE, $listing->fresh()->status);
    }
}
