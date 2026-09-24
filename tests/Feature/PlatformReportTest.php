<?php

namespace Tests\Feature;

use App\Models\BuyerOrder;
use App\Models\BuyerProfile;
use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformReportTest extends TestCase
{
    use RefreshDatabase;

    private function coop(string $email, string $status = Cooperative::STATUS_APPROVED): Cooperative
    {
        return Cooperative::create([
            'name' => 'Coop '.$email, 'type' => 'primary', 'contact_number' => '09171234567',
            'official_email' => $email, 'status' => $status,
        ]);
    }

    public function test_platform_report_aggregates_counts_across_cooperatives(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);

        $coopA = $this->coop('a@example.com', Cooperative::STATUS_APPROVED);
        $this->coop('b@example.com', Cooperative::STATUS_PENDING);

        User::factory()->count(3)->create(['role' => UserRole::FARMER->value]);

        $buyerApproved = User::factory()->create(['role' => UserRole::BUYER->value]);
        BuyerProfile::create(['user_id' => $buyerApproved->id, 'status' => BuyerProfile::STATUS_APPROVED, 'business_name' => 'Acme']);
        $buyerPending = User::factory()->create(['role' => UserRole::BUYER->value]);
        BuyerProfile::create(['user_id' => $buyerPending->id, 'status' => BuyerProfile::STATUS_PENDING, 'business_name' => 'Pending Co']);

        BuyerOrder::create([
            'cooperative_id' => $coopA->id, 'buyer_id' => $buyerApproved->id, 'reference' => 'ORD-1',
            'status' => BuyerOrder::STATUS_COMPLETED, 'total_kg' => 100, 'total_amount' => 5000,
        ]);
        BuyerOrder::create([
            'cooperative_id' => $coopA->id, 'buyer_id' => $buyerApproved->id, 'reference' => 'ORD-2',
            'status' => BuyerOrder::STATUS_SUBMITTED, 'total_kg' => 50, 'total_amount' => 2000,
        ]);

        HaulJob::create([
            'cooperative_id' => $coopA->id, 'job_type' => HaulJob::JOB_TYPE_PICKUP,
            'pickup_date' => today(), 'status' => HaulJob::STATUS_COMPLETED,
        ]);
        HaulJob::create([
            'cooperative_id' => $coopA->id, 'job_type' => HaulJob::JOB_TYPE_DELIVERY,
            'pickup_date' => today(), 'status' => HaulJob::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertViewHas('totalCooperatives', 2);
        $response->assertViewHas('approvedCooperatives', 1);
        $response->assertViewHas('totalFarmers', 3);
        $response->assertViewHas('activeBuyers', 1);
        $response->assertViewHas('completedOrders', 1);
        $response->assertViewHas('completedTrips', 1);
        $response->assertViewHas('transactionVolume', fn ($v) => (float) $v === 5000.0);
    }

    public function test_platform_report_blocked_for_non_super_admin(): void
    {
        $coop = $this->coop('c@example.com');
        $coopAdmin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);

        $response = $this->actingAs($coopAdmin)->get(route('admin.reports.index'));

        $response->assertForbidden();
    }
}
