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

class FarmerPaymentTest extends TestCase
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

    private function confirmedRecord(Cooperative $coop, array $overrides = []): ReceivingRecord
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
            'recorded_by' => $driver->id, 'status' => ReceivingRecord::STATUS_CONFIRMED,
            'buying_price_per_kg' => 18, 'total_amount' => 70560,
            'confirmed_by' => null, 'confirmed_at' => now(),
        ], $overrides));
    }

    private function coopAdmin(Cooperative $coop): User
    {
        return User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id, 'email_verified_at' => now()]);
    }

    public function test_partial_payment_moves_status_to_partial(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->confirmedRecord($coop);

        $response = $this->actingAs($admin)->post(route('coop.procurement.payments.store', $record), [
            'amount' => 50000,
            'method' => 'bank_transfer',
            'reference' => 'BT-928381',
        ]);

        $response->assertRedirect();
        $record->refresh();
        $this->assertEquals(50000.00, $record->totalPaid());
        $this->assertEquals(20560.00, $record->balanceDue());
        $this->assertEquals('partial', $record->paymentStatus());
    }

    public function test_full_payment_moves_status_to_paid(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->confirmedRecord($coop);

        $this->actingAs($admin)->post(route('coop.procurement.payments.store', $record), [
            'amount' => 70560,
            'method' => 'cash',
        ]);

        $record->refresh();
        $this->assertEquals(0.0, $record->balanceDue());
        $this->assertEquals('paid', $record->paymentStatus());
    }

    public function test_multiple_partial_payments_accumulate(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->confirmedRecord($coop);

        $this->actingAs($admin)->post(route('coop.procurement.payments.store', $record), ['amount' => 30000, 'method' => 'cash']);
        $this->actingAs($admin)->post(route('coop.procurement.payments.store', $record), ['amount' => 40560, 'method' => 'cash']);

        $record->refresh();
        $this->assertCount(2, $record->payments);
        $this->assertEquals('paid', $record->paymentStatus());
    }

    public function test_overpayment_is_blocked(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->confirmedRecord($coop);

        $response = $this->actingAs($admin)->post(route('coop.procurement.payments.store', $record), [
            'amount' => 999999,
            'method' => 'cash',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('farmer_payments', 0);
    }

    public function test_payment_blocked_before_confirmed(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->confirmedRecord($coop, ['status' => ReceivingRecord::STATUS_PRICED]);

        $response = $this->actingAs($admin)->post(route('coop.procurement.payments.store', $record), [
            'amount' => 1000,
            'method' => 'cash',
        ]);

        $response->assertSessionHasErrors('receiving');
        $this->assertDatabaseCount('farmer_payments', 0);
    }

    public function test_payment_blocked_once_already_fully_paid(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->confirmedRecord($coop);
        $this->actingAs($admin)->post(route('coop.procurement.payments.store', $record), ['amount' => 70560, 'method' => 'cash']);

        $response = $this->actingAs($admin)->post(route('coop.procurement.payments.store', $record), ['amount' => 1, 'method' => 'cash']);

        $response->assertSessionHasErrors('receiving');
    }

    public function test_other_cooperative_cannot_record_payment(): void
    {
        $coop = $this->cooperative('GenSan AgCoop');
        $otherCoop = $this->cooperative('Davao AgCoop');
        $otherAdmin = $this->coopAdmin($otherCoop);
        $record = $this->confirmedRecord($coop);

        $response = $this->actingAs($otherAdmin)->post(route('coop.procurement.payments.store', $record), [
            'amount' => 1000,
            'method' => 'cash',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('farmer_payments', 0);
    }

    public function test_payment_writes_audit_log(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $record = $this->confirmedRecord($coop);

        $this->actingAs($admin)->post(route('coop.procurement.payments.store', $record), ['amount' => 1000, 'method' => 'cash']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'record_farmer_payment', 'target_id' => $record->id]);
    }
}
