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

class ConsolidationReportTest extends TestCase
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

    private function pickupJobWithStops(Cooperative $coop, Truck $truck, User $driver, array $weights): HaulJob
    {
        $job = HaulJob::create([
            'cooperative_id' => $coop->id, 'job_type' => HaulJob::JOB_TYPE_PICKUP, 'truck_id' => $truck->id,
            'delivery_personnel_id' => $driver->id, 'pickup_date' => today(), 'status' => HaulJob::STATUS_COMPLETED,
        ]);

        foreach ($weights as $i => $weight) {
            $req = HaulRequest::factory()->create(['cooperative_id' => $coop->id, 'estimated_weight_kg' => $weight]);
            HaulJobStop::create([
                'haul_job_id' => $job->id, 'haul_request_id' => $req->id,
                'sequence_no' => $i + 1, 'status' => HaulJobStop::STATUS_PICKED_UP,
            ]);
        }

        return $job;
    }

    public function test_consolidation_report_computes_load_capacity_and_utilization(): void
    {
        $coop = $this->coop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 4000]);

        $this->pickupJobWithStops($coop, $truck, $driver, [1200, 800]);

        $response = $this->actingAs($admin)->get(route('coop.reports.consolidation'));

        $response->assertOk();
        $response->assertViewHas('totalTrips', 1);
        $response->assertViewHas('totalStops', 2);
        $response->assertViewHas('totalLoad', fn ($v) => (float) $v === 2000.0);
        $response->assertViewHas('avgUtilization', fn ($v) => (float) $v === 50.0);
    }

    public function test_consolidation_report_excludes_outbound_delivery_jobs(): void
    {
        $coop = $this->coop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id]);

        HaulJob::create([
            'cooperative_id' => $coop->id, 'job_type' => HaulJob::JOB_TYPE_DELIVERY, 'truck_id' => $truck->id,
            'delivery_personnel_id' => $driver->id, 'pickup_date' => today(), 'status' => HaulJob::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($admin)->get(route('coop.reports.consolidation'));

        $response->assertOk();
        $response->assertViewHas('totalTrips', 0);
    }

    public function test_consolidation_report_excludes_other_cooperative(): void
    {
        $coop = $this->coop();
        $otherCoop = $this->coop('other@example.com');
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $otherCoop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $otherCoop->id]);

        $this->pickupJobWithStops($otherCoop, $truck, $driver, [500]);

        $response = $this->actingAs($admin)->get(route('coop.reports.consolidation'));

        $response->assertOk();
        $response->assertViewHas('totalTrips', 0);
    }

    public function test_consolidation_report_exposes_csv_and_pdf_downloads(): void
    {
        $coop = $this->coop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);

        $csv = $this->actingAs($admin)->get(route('coop.reports.consolidation.csv'));
        $csv->assertOk();

        $pdf = $this->actingAs($admin)->get(route('coop.reports.consolidation.pdf'));
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
    }
}
