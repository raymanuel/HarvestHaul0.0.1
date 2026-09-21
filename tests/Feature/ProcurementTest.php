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

class ProcurementTest extends TestCase
{
    use RefreshDatabase;

    private function cooperative(): Cooperative
    {
        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
    }

    private function pendingRecord(Cooperative $coop, array $overrides = []): ReceivingRecord
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

    public function test_set_price_moves_pending_to_priced(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->pendingRecord($coop);

        $response = $this->actingAs($admin)->post(route('coop.procurement.price', $record), [
            'buying_price_per_kg' => 18,
        ]);

        $response->assertRedirect();
        $record->refresh();
        $this->assertEquals(ReceivingRecord::STATUS_PRICED, $record->status);
        $this->assertEquals(70560.00, (float) $record->total_amount);
        $this->assertNull($record->confirmed_by);
    }

    public function test_confirm_blocked_while_still_pending(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->pendingRecord($coop);

        $response = $this->actingAs($admin)->post(route('coop.procurement.confirm', $record));

        $response->assertSessionHasErrors('receiving');
        $record->refresh();
        $this->assertEquals(ReceivingRecord::STATUS_PENDING, $record->status);
    }

    public function test_confirm_moves_priced_to_confirmed_and_notifies_farmer(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->pendingRecord($coop, [
            'status' => ReceivingRecord::STATUS_PRICED,
            'buying_price_per_kg' => 18,
            'total_amount' => 70560,
        ]);

        $response = $this->actingAs($admin)->post(route('coop.procurement.confirm', $record));

        $response->assertRedirect();
        $record->refresh();
        $this->assertEquals(ReceivingRecord::STATUS_CONFIRMED, $record->status);
        $this->assertEquals($admin->id, $record->confirmed_by);
        $this->assertNotNull($record->confirmed_at);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $record->farmer_id,
            'category' => 'procurement',
        ]);

        // Crop becomes sellable inventory only now, at confirmation.
        $availability = CropAvailability::where('receiving_record_id', $record->id)->first();
        $this->assertNotNull($availability);
        $this->assertEquals(3920.00, (float) $availability->quantity_kg);
        $this->assertNull($availability->selling_price_per_kg);
        $this->assertEquals(CropAvailability::STATUS_AVAILABLE, $availability->status);
    }

    public function test_cancel_from_pending(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->pendingRecord($coop);

        $response = $this->actingAs($admin)->post(route('coop.procurement.cancel', $record), [
            'cancellation_reason' => 'Duplicate entry for this stop.',
        ]);

        $response->assertRedirect();
        $record->refresh();
        $this->assertEquals(ReceivingRecord::STATUS_CANCELLED, $record->status);
        $this->assertEquals($admin->id, $record->cancelled_by);
        $this->assertEquals('Duplicate entry for this stop.', $record->cancellation_reason);
    }

    public function test_cancel_from_priced_also_archives_linked_availability(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->pendingRecord($coop, ['status' => ReceivingRecord::STATUS_PRICED, 'buying_price_per_kg' => 18, 'total_amount' => 70560]);
        $availability = CropAvailability::create([
            'cooperative_id' => $coop->id, 'receiving_record_id' => $record->id,
            'crop_id' => $record->crop_id, 'crop_grade_id' => $record->crop_grade_id,
            'quantity_kg' => 3920, 'status' => CropAvailability::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)->post(route('coop.procurement.cancel', $record), [
            'cancellation_reason' => 'Wrong weight recorded.',
        ]);

        $record->refresh();
        $availability->refresh();
        $this->assertEquals(ReceivingRecord::STATUS_CANCELLED, $record->status);
        $this->assertEquals(CropAvailability::STATUS_ARCHIVED, $availability->status);
    }

    public function test_cancel_blocked_once_confirmed(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->pendingRecord($coop, [
            'status' => ReceivingRecord::STATUS_CONFIRMED,
            'buying_price_per_kg' => 18, 'total_amount' => 70560,
            'confirmed_by' => $admin->id, 'confirmed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('coop.procurement.cancel', $record), [
            'cancellation_reason' => 'Changed my mind.',
        ]);

        $response->assertSessionHasErrors('receiving');
        $record->refresh();
        $this->assertEquals(ReceivingRecord::STATUS_CONFIRMED, $record->status);
    }

    public function test_cancel_blocked_if_availability_already_sold(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->pendingRecord($coop, ['status' => ReceivingRecord::STATUS_PRICED, 'buying_price_per_kg' => 18, 'total_amount' => 70560]);
        CropAvailability::create([
            'cooperative_id' => $coop->id, 'receiving_record_id' => $record->id,
            'crop_id' => $record->crop_id, 'crop_grade_id' => $record->crop_grade_id,
            'quantity_kg' => 3920, 'sold_kg' => 500, 'status' => CropAvailability::STATUS_AVAILABLE,
        ]);

        $response = $this->actingAs($admin)->post(route('coop.procurement.cancel', $record), [
            'cancellation_reason' => 'Trying anyway.',
        ]);

        $response->assertSessionHasErrors('receiving');
        $record->refresh();
        $this->assertEquals(ReceivingRecord::STATUS_PRICED, $record->status);
    }
}
