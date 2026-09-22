<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    private function pickupTrip(): array
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $crop = \App\Models\Crop::factory()->create();

        $haulRequest = HaulRequest::create([
            'farmer_id' => $farmer->id, 'cooperative_id' => $coop->id, 'crop_id' => $crop->id,
            'estimated_weight_kg' => 100, 'status' => HaulRequest::STATUS_SCHEDULED,
        ]);
        $job = HaulJob::create([
            'haul_request_id' => $haulRequest->id, 'cooperative_id' => $coop->id, 'job_type' => HaulJob::JOB_TYPE_PICKUP,
            'delivery_personnel_id' => $driver->id, 'pickup_date' => today(), 'status' => HaulJob::STATUS_SCHEDULED,
        ]);
        $stop = HaulJobStop::create([
            'haul_job_id' => $job->id, 'haul_request_id' => $haulRequest->id,
            'sequence_no' => 1, 'status' => HaulJobStop::STATUS_PENDING,
        ]);

        return compact('coop', 'driver', 'job', 'stop');
    }

    public function test_stop_status_update_writes_audit_log(): void
    {
        $ctx = $this->pickupTrip();

        $this->actingAs($ctx['driver'])->post(route('delivery.trips.stop-status', [$ctx['stop'], 'arrived']));

        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $ctx['driver']->id, 'action' => 'update_stop_status',
            'target_type' => 'haul_job_stop', 'target_id' => $ctx['stop']->id,
        ]);
    }

    public function test_trip_completion_writes_audit_log(): void
    {
        $ctx = $this->pickupTrip();

        $this->actingAs($ctx['driver'])->post(route('delivery.trips.stop-status', [$ctx['stop'], 'picked_up']));

        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $ctx['driver']->id, 'action' => 'complete_haul_job',
            'target_type' => 'haul_job', 'target_id' => $ctx['job']->id,
        ]);
    }

    public function test_admin_can_filter_audit_logs_by_action(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'email_verified_at' => now()]);

        AuditLog::create(['admin_id' => $admin->id, 'action' => 'created_crop', 'target_type' => 'crop', 'target_id' => 1, 'notes' => 'test']);
        AuditLog::create(['admin_id' => $admin->id, 'action' => 'deleted_crop', 'target_type' => 'crop', 'target_id' => 1, 'notes' => 'test']);

        $response = $this->actingAs($admin)->get(route('admin.audit-logs', ['action' => 'created_crop']));

        $response->assertOk();
        $response->assertViewHas('logs', fn ($logs) => $logs->total() === 1);
    }

    public function test_admin_can_filter_audit_logs_by_date_range(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'email_verified_at' => now()]);

        $old = AuditLog::create(['admin_id' => $admin->id, 'action' => 'created_crop', 'target_type' => 'crop', 'target_id' => 1, 'notes' => 'old']);
        $old->created_at = now()->subMonths(2);
        $old->save();
        AuditLog::create(['admin_id' => $admin->id, 'action' => 'created_crop', 'target_type' => 'crop', 'target_id' => 2, 'notes' => 'recent']);

        $response = $this->actingAs($admin)->get(route('admin.audit-logs', [
            'from' => now()->subDays(1)->toDateString(), 'to' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertViewHas('logs', fn ($logs) => $logs->total() === 1);
    }

    public function test_non_admin_cannot_view_audit_logs(): void
    {
        $coopAdmin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value]);

        $response = $this->actingAs($coopAdmin)->get(route('admin.audit-logs'));

        $response->assertForbidden();
    }
}
