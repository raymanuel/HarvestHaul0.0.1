<?php

namespace Tests\Feature;

use App\Models\BuyerOrder;
use App\Models\Cooperative;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DetectDelayedStopsTest extends TestCase
{
    use RefreshDatabase;

    private function coopWithAdmin(): array
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);

        return [$coop, $admin];
    }

    private function pickupStop(Cooperative $coop, ?\Carbon\Carbon $plannedArrival, string $jobStatus = HaulJob::STATUS_SCHEDULED, string $stopStatus = HaulJobStop::STATUS_PENDING): array
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $haulRequest = HaulRequest::factory()->create(['farmer_id' => $farmer->id, 'cooperative_id' => $coop->id]);
        $haulJob = HaulJob::factory()->create(['cooperative_id' => $coop->id, 'haul_request_id' => $haulRequest->id, 'status' => $jobStatus]);
        $stop = HaulJobStop::factory()->create([
            'haul_job_id' => $haulJob->id,
            'haul_request_id' => $haulRequest->id,
            'planned_arrival_at' => $plannedArrival,
            'status' => $stopStatus,
        ]);

        return [$stop, $farmer];
    }

    public function test_a_stop_past_the_threshold_gets_flagged_and_both_parties_notified(): void
    {
        [$coop, $admin] = $this->coopWithAdmin();
        [$stop, $farmer] = $this->pickupStop($coop, now()->subMinutes(25));

        $this->artisan('haul:detect-delays')->assertExitCode(0);

        $stop->refresh();
        $this->assertNotNull($stop->delay_notified_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'category' => 'haul']);
        $this->assertDatabaseHas('notifications', ['user_id' => $farmer->id, 'category' => 'haul']);
    }

    public function test_a_stop_not_yet_past_the_threshold_is_not_flagged(): void
    {
        [$coop, $admin] = $this->coopWithAdmin();
        [$stop] = $this->pickupStop($coop, now()->subMinutes(5));

        $this->artisan('haul:detect-delays');

        $this->assertNull($stop->refresh()->delay_notified_at);
        $this->assertDatabaseMissing('notifications', ['user_id' => $admin->id]);
    }

    public function test_a_stop_already_flagged_is_never_notified_twice(): void
    {
        [$coop, $admin] = $this->coopWithAdmin();
        [$stop] = $this->pickupStop($coop, now()->subMinutes(25));
        $stop->update(['delay_notified_at' => now()->subMinutes(10)]);

        $this->artisan('haul:detect-delays');

        $this->assertDatabaseMissing('notifications', ['user_id' => $admin->id]);
    }

    public function test_a_stop_with_no_planned_arrival_is_never_flagged(): void
    {
        [$coop, $admin] = $this->coopWithAdmin();
        [$stop] = $this->pickupStop($coop, null);

        $this->artisan('haul:detect-delays');

        $this->assertNull($stop->refresh()->delay_notified_at);
        $this->assertDatabaseMissing('notifications', ['user_id' => $admin->id]);
    }

    public function test_a_stop_on_a_completed_job_is_not_flagged(): void
    {
        [$coop, $admin] = $this->coopWithAdmin();
        [$stop] = $this->pickupStop($coop, now()->subMinutes(25), HaulJob::STATUS_COMPLETED, HaulJobStop::STATUS_PICKED_UP);

        $this->artisan('haul:detect-delays');

        $this->assertNull($stop->refresh()->delay_notified_at);
        $this->assertDatabaseMissing('notifications', ['user_id' => $admin->id]);
    }

    public function test_a_stop_already_arrived_or_delivered_is_not_flagged(): void
    {
        [$coop, $admin] = $this->coopWithAdmin();
        [$stop] = $this->pickupStop($coop, now()->subMinutes(25), HaulJob::STATUS_PICKED_UP, HaulJobStop::STATUS_PICKED_UP);

        $this->artisan('haul:detect-delays');

        $this->assertNull($stop->refresh()->delay_notified_at);
    }

    public function test_a_delivery_stop_notifies_the_buyer_not_a_farmer(): void
    {
        [$coop, $admin] = $this->coopWithAdmin();
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        $buyerOrder = BuyerOrder::create([
            'buyer_id' => $buyer->id, 'cooperative_id' => $coop->id,
            'reference' => 'ORD-'.Str::random(8), 'status' => BuyerOrder::STATUS_READY_FOR_DELIVERY,
            'total_kg' => 100, 'total_amount' => 5000,
        ]);
        $haulJob = HaulJob::factory()->create(['cooperative_id' => $coop->id, 'status' => HaulJob::STATUS_SCHEDULED, 'job_type' => 'delivery']);
        $stop = HaulJobStop::factory()->create([
            'haul_job_id' => $haulJob->id,
            'haul_request_id' => null,
            'buyer_order_id' => $buyerOrder->id,
            'planned_arrival_at' => now()->subMinutes(30),
        ]);

        $this->artisan('haul:detect-delays');

        $this->assertNotNull($stop->refresh()->delay_notified_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'category' => 'haul']);
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'category' => 'haul']);
    }
}
