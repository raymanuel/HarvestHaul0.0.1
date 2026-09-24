<?php

namespace Tests\Feature;

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

class FarmerReportTest extends TestCase
{
    use RefreshDatabase;

    private function coop(): Cooperative
    {
        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary', 'contact_number' => '09171234567',
            'official_email' => 'coop@example.com', 'status' => Cooperative::STATUS_APPROVED,
        ]);
    }

    private function record(Cooperative $coop, User $farmer, float $weight, float $pricePerKg, float $paid, ?Crop $crop = null, string $status = ReceivingRecord::STATUS_CONFIRMED): ReceivingRecord
    {
        $crop ??= Crop::factory()->create();
        $total = round($weight * $pricePerKg, 2);

        $job = HaulJob::create([
            'cooperative_id' => $coop->id, 'job_type' => HaulJob::JOB_TYPE_PICKUP,
            'pickup_date' => today(), 'status' => HaulJob::STATUS_COMPLETED,
        ]);
        $recorder = User::factory()->create(['role' => UserRole::FIELD_RECEIVING->value, 'cooperative_id' => $coop->id]);

        $record = ReceivingRecord::create([
            'haul_job_id' => $job->id, 'cooperative_id' => $coop->id, 'farmer_id' => $farmer->id,
            'crop_id' => $crop->id, 'crop_grade_id' => CropGrade::factory()->create()->id,
            'actual_weight_kg' => $weight, 'buying_price_per_kg' => $pricePerKg, 'total_amount' => $total,
            'status' => $status, 'confirmed_at' => $status === ReceivingRecord::STATUS_CONFIRMED ? now() : null,
            'recorded_by' => $recorder->id,
        ]);

        if ($paid > 0) {
            FarmerPayment::create([
                'receiving_record_id' => $record->id, 'cooperative_id' => $coop->id,
                'amount' => $paid, 'method' => FarmerPayment::METHOD_CASH, 'paid_at' => now(),
                'recorded_by' => $recorder->id,
            ]);
        }

        return $record;
    }

    public function test_farmer_report_sums_own_confirmed_harvests_and_payments(): void
    {
        $coop = $this->coop();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);

        $this->record($coop, $farmer, 1000, 20, 15000);
        $this->record($coop, $farmer, 500, 25, 0);

        $response = $this->actingAs($farmer)->get(route('farmer.reports.index'));

        $response->assertOk();
        $response->assertViewHas('totalWeight', fn ($v) => (float) $v === 1500.0);
        $response->assertViewHas('totalEarnings', fn ($v) => (float) $v === 32500.0);
        $response->assertViewHas('totalPaid', fn ($v) => (float) $v === 15000.0);
        $response->assertViewHas('totalOutstanding', fn ($v) => (float) $v === 17500.0);
    }

    public function test_farmer_report_breaks_earnings_down_by_crop(): void
    {
        $coop = $this->coop();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $rice = Crop::factory()->create(['name' => 'Rice']);
        $corn = Crop::factory()->create(['name' => 'Corn']);

        $this->record($coop, $farmer, 1000, 20, 0, $rice);
        $this->record($coop, $farmer, 500, 20, 0, $rice);
        $this->record($coop, $farmer, 300, 15, 0, $corn);

        $response = $this->actingAs($farmer)->get(route('farmer.reports.index'));

        $response->assertOk();
        $response->assertViewHas('byCrop', function ($byCrop) {
            $rice = $byCrop->firstWhere('crop', 'Rice');
            $corn = $byCrop->firstWhere('crop', 'Corn');

            return $rice && $corn
                && $rice['count'] === 2 && (float) $rice['weight'] === 1500.0 && (float) $rice['earnings'] === 30000.0
                && $corn['count'] === 1 && (float) $corn['weight'] === 300.0 && (float) $corn['earnings'] === 4500.0;
        });
    }

    public function test_farmer_report_excludes_another_farmers_records(): void
    {
        $coop = $this->coop();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $otherFarmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);

        $this->record($coop, $otherFarmer, 1000, 20, 0);

        $response = $this->actingAs($farmer)->get(route('farmer.reports.index'));

        $response->assertOk();
        $response->assertViewHas('totalWeight', fn ($v) => (float) $v === 0.0);
    }

    public function test_farmer_report_excludes_unconfirmed_records(): void
    {
        $coop = $this->coop();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);

        $this->record($coop, $farmer, 300, 20, 0, null, ReceivingRecord::STATUS_PRICED);

        $response = $this->actingAs($farmer)->get(route('farmer.reports.index'));

        $response->assertOk();
        $response->assertViewHas('totalWeight', fn ($v) => (float) $v === 0.0);
    }

    public function test_farmer_report_exposes_csv_and_pdf_downloads(): void
    {
        $coop = $this->coop();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);

        $csv = $this->actingAs($farmer)->get(route('farmer.reports.csv'));
        $csv->assertOk();

        $pdf = $this->actingAs($farmer)->get(route('farmer.reports.pdf'));
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
    }

    public function test_non_farmer_cannot_access_farmer_report(): void
    {
        $coop = $this->coop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);

        $response = $this->actingAs($admin)->get(route('farmer.reports.index'));

        $response->assertForbidden();
    }
}
