<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryFleetReportTest extends TestCase
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

    private function job(Cooperative $coop, Truck $truck, User $driver, string $jobType, float $distance, array $stopStatuses): HaulJob
    {
        $job = HaulJob::create([
            'cooperative_id' => $coop->id, 'job_type' => $jobType, 'truck_id' => $truck->id,
            'delivery_personnel_id' => $driver->id, 'pickup_date' => today(), 'status' => HaulJob::STATUS_COMPLETED,
            'route_distance_km' => $distance,
        ]);
        foreach ($stopStatuses as $i => $status) {
            HaulJobStop::create(['haul_job_id' => $job->id, 'sequence_no' => $i + 1, 'status' => $status]);
        }

        return $job;
    }

    public function test_fleet_report_summarizes_trips_distance_and_stops(): void
    {
        $coop = $this->coop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id]);

        $this->job($coop, $truck, $driver, HaulJob::JOB_TYPE_PICKUP, 10.0, [HaulJobStop::STATUS_PICKED_UP]);
        $this->job($coop, $truck, $driver, HaulJob::JOB_TYPE_DELIVERY, 15.0, [HaulJobStop::STATUS_DELIVERED, HaulJobStop::STATUS_FAILED]);

        $response = $this->actingAs($admin)->get(route('coop.reports.deliveries'));

        $response->assertOk();
        $response->assertViewHas('totalTrips', 2);
        $response->assertViewHas('totalDistance', fn ($d) => (float) $d === 25.0);
        $response->assertViewHas('totalStops', 3);
        $response->assertViewHas('deliveredStops', 2);
        $response->assertViewHas('failedStops', 1);
    }

    public function test_fleet_report_groups_by_vehicle(): void
    {
        $coop = $this->coop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truckA = Truck::factory()->create(['cooperative_id' => $coop->id]);
        $truckB = Truck::factory()->create(['cooperative_id' => $coop->id]);

        $this->job($coop, $truckA, $driver, HaulJob::JOB_TYPE_PICKUP, 10.0, []);
        $this->job($coop, $truckA, $driver, HaulJob::JOB_TYPE_PICKUP, 5.0, []);
        $this->job($coop, $truckB, $driver, HaulJob::JOB_TYPE_DELIVERY, 20.0, []);

        $response = $this->actingAs($admin)->get(route('coop.reports.deliveries'));

        $response->assertOk();
        $response->assertViewHas('vehicles', function ($vehicles) use ($truckA) {
            $row = $vehicles->firstWhere('truck.id', $truckA->id);

            return $row && $row['trips'] === 2 && (float) $row['distance'] === 15.0;
        });
    }

    public function test_fleet_report_excludes_other_cooperative(): void
    {
        $coop = $this->coop();
        $otherCoop = $this->coop('other@example.com');
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $otherCoop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $otherCoop->id]);

        $this->job($otherCoop, $truck, $driver, HaulJob::JOB_TYPE_PICKUP, 10.0, []);

        $response = $this->actingAs($admin)->get(route('coop.reports.deliveries'));

        $response->assertOk();
        $response->assertViewHas('totalTrips', 0);
    }

    public function test_all_four_reports_expose_pdf_downloads(): void
    {
        $coop = $this->coop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);

        foreach (['procurement', 'sales', 'payouts', 'deliveries'] as $report) {
            $response = $this->actingAs($admin)->get(route("coop.reports.{$report}.pdf"));
            $response->assertOk();
            $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        }
    }
}
