<?php

namespace Tests\Feature;

use App\Models\BuyerOrder;
use App\Models\Cooperative;
use App\Models\Crop;
use App\Models\CropGrade;
use App\Models\FarmerPayment;
use App\Models\HaulJob;
use App\Models\ReceivingRecord;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoopReportTest extends TestCase
{
    use RefreshDatabase;

    private function coop(string $email = 'coop@example.com'): Cooperative
    {
        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => $email,
            'status' => Cooperative::STATUS_APPROVED,
        ]);
    }

    private function confirmedReceiving(Cooperative $coop, User $farmer, Crop $crop, CropGrade $grade, float $weight, float $price, \DateTimeInterface $confirmedAt): ReceivingRecord
    {
        $job = HaulJob::create(['cooperative_id' => $coop->id, 'job_type' => HaulJob::JOB_TYPE_PICKUP, 'pickup_date' => today(), 'status' => HaulJob::STATUS_SCHEDULED]);
        $recorder = User::factory()->create(['role' => UserRole::FIELD_RECEIVING->value, 'cooperative_id' => $coop->id]);

        return ReceivingRecord::create([
            'haul_job_id' => $job->id, 'cooperative_id' => $coop->id, 'farmer_id' => $farmer->id,
            'crop_id' => $crop->id, 'crop_grade_id' => $grade->id,
            'actual_weight_kg' => $weight, 'buying_price_per_kg' => $price, 'total_amount' => $weight * $price,
            'status' => ReceivingRecord::STATUS_CONFIRMED, 'confirmed_at' => $confirmedAt,
            'recorded_by' => $recorder->id,
        ]);
    }

    public function test_procurement_report_sums_confirmed_records_in_range(): void
    {
        $coop = $this->coop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $crop = Crop::factory()->create();
        $grade = CropGrade::create(['name' => 'Class A', 'is_active' => true, 'sort_order' => 1]);

        $this->confirmedReceiving($coop, $farmer, $crop, $grade, 100, 20, now()); // in range
        $this->confirmedReceiving($coop, $farmer, $crop, $grade, 50, 20, now()->subMonths(2)); // out of range

        $response = $this->actingAs($admin)->get(route('coop.reports.procurement'));

        $response->assertOk();
        $response->assertViewHas('totalWeight', fn ($w) => (float) $w === 100.0);
        $response->assertViewHas('totalSpend', fn ($s) => (float) $s === 2000.0);
    }

    public function test_procurement_report_excludes_other_cooperative(): void
    {
        $coop = $this->coop();
        $otherCoop = $this->coop('other@example.com');
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $otherCoop->id]);
        $crop = Crop::factory()->create();
        $grade = CropGrade::create(['name' => 'Class A', 'is_active' => true, 'sort_order' => 1]);

        $this->confirmedReceiving($otherCoop, $farmer, $crop, $grade, 100, 20, now());

        $response = $this->actingAs($admin)->get(route('coop.reports.procurement'));

        $response->assertOk();
        $response->assertViewHas('records', fn ($records) => $records->isEmpty());
    }

    public function test_procurement_csv_downloads(): void
    {
        $coop = $this->coop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $crop = Crop::factory()->create();
        $grade = CropGrade::create(['name' => 'Class A', 'is_active' => true, 'sort_order' => 1]);
        $this->confirmedReceiving($coop, $farmer, $crop, $grade, 100, 20, now());

        $response = $this->actingAs($admin)->get(route('coop.reports.procurement.csv'));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=procurement-report.csv');
    }

    public function test_sales_report_sums_booked_orders_in_range_excludes_pending(): void
    {
        $coop = $this->coop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);

        BuyerOrder::create([
            'buyer_id' => $buyer->id, 'cooperative_id' => $coop->id, 'reference' => 'ORD-1',
            'status' => BuyerOrder::STATUS_ACCEPTED, 'total_kg' => 100, 'total_amount' => 2500,
            'delivery_address' => 'Somewhere',
        ]);
        BuyerOrder::create([
            'buyer_id' => $buyer->id, 'cooperative_id' => $coop->id, 'reference' => 'ORD-2',
            'status' => BuyerOrder::STATUS_SUBMITTED, 'total_kg' => 50, 'total_amount' => 1250,
            'delivery_address' => 'Somewhere',
        ]);

        $response = $this->actingAs($admin)->get(route('coop.reports.sales'));

        $response->assertOk();
        $response->assertViewHas('totalRevenue', fn ($r) => (float) $r === 2500.0);
        $response->assertViewHas('orders', fn ($orders) => $orders->count() === 1);
    }

    public function test_payouts_report_sums_payments_in_range(): void
    {
        $coop = $this->coop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $crop = Crop::factory()->create();
        $grade = CropGrade::create(['name' => 'Class A', 'is_active' => true, 'sort_order' => 1]);
        $receiving = $this->confirmedReceiving($coop, $farmer, $crop, $grade, 100, 20, now());

        FarmerPayment::create([
            'receiving_record_id' => $receiving->id, 'cooperative_id' => $coop->id,
            'amount' => 1500, 'method' => FarmerPayment::METHOD_CASH, 'paid_at' => now(), 'recorded_by' => $admin->id,
        ]);
        FarmerPayment::create([
            'receiving_record_id' => $receiving->id, 'cooperative_id' => $coop->id,
            'amount' => 500, 'method' => FarmerPayment::METHOD_CASH, 'paid_at' => now()->subMonths(3), 'recorded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('coop.reports.payouts'));

        $response->assertOk();
        $response->assertViewHas('totalPaid', fn ($t) => (float) $t === 1500.0);
    }

    public function test_reports_blocked_for_non_coop_admin(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);

        $response = $this->actingAs($farmer)->get(route('coop.reports.index'));

        $response->assertForbidden();
    }
}
