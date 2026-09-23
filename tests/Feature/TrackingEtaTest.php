<?php

namespace Tests\Feature;

use App\Models\BuyerOrder;
use App\Models\Cooperative;
use Illuminate\Support\Str;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingEtaTest extends TestCase
{
    use RefreshDatabase;

    private function coop(): Cooperative
    {
        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
    }

    public function test_farmer_sees_planned_arrival_when_it_exists(): void
    {
        $coop = $this->coop();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $haulRequest = HaulRequest::factory()->create(['farmer_id' => $farmer->id, 'cooperative_id' => $coop->id]);
        $haulJob = HaulJob::factory()->create(['cooperative_id' => $coop->id, 'haul_request_id' => $haulRequest->id, 'status' => HaulJob::STATUS_SCHEDULED]);
        $stop = HaulJobStop::factory()->create([
            'haul_job_id' => $haulJob->id,
            'haul_request_id' => $haulRequest->id,
            'planned_arrival_at' => now()->addHours(2),
        ]);

        $response = $this->actingAs($farmer)->get(route('farmer.haul-requests.track', $haulRequest));

        $response->assertOk();
        $response->assertSee($stop->planned_arrival_at->format('M d, g:i A'));
    }

    public function test_farmer_sees_not_yet_estimated_when_planned_arrival_is_null(): void
    {
        $coop = $this->coop();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $haulRequest = HaulRequest::factory()->create(['farmer_id' => $farmer->id, 'cooperative_id' => $coop->id]);
        $haulJob = HaulJob::factory()->create(['cooperative_id' => $coop->id, 'haul_request_id' => $haulRequest->id, 'status' => HaulJob::STATUS_SCHEDULED]);
        HaulJobStop::factory()->create([
            'haul_job_id' => $haulJob->id,
            'haul_request_id' => $haulRequest->id,
            'planned_arrival_at' => null,
        ]);

        $response = $this->actingAs($farmer)->get(route('farmer.haul-requests.track', $haulRequest));

        $response->assertOk();
        $response->assertSee('Arrival not yet estimated.');
    }

    public function test_farmer_sees_running_late_badge_instead_of_stale_time_when_flagged(): void
    {
        $coop = $this->coop();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $haulRequest = HaulRequest::factory()->create(['farmer_id' => $farmer->id, 'cooperative_id' => $coop->id]);
        $haulJob = HaulJob::factory()->create(['cooperative_id' => $coop->id, 'haul_request_id' => $haulRequest->id, 'status' => HaulJob::STATUS_SCHEDULED]);
        $stop = HaulJobStop::factory()->create([
            'haul_job_id' => $haulJob->id,
            'haul_request_id' => $haulRequest->id,
            'planned_arrival_at' => now()->subHour(),
            'delay_notified_at' => now(),
        ]);

        $response = $this->actingAs($farmer)->get(route('farmer.haul-requests.track', $haulRequest));

        $response->assertOk();
        $response->assertSee('Running late');
        $response->assertDontSee($stop->planned_arrival_at->format('M d, g:i A'));
    }

    public function test_buyer_sees_planned_arrival_when_it_exists(): void
    {
        $coop = $this->coop();
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        $haulJob = HaulJob::factory()->create(['cooperative_id' => $coop->id, 'status' => HaulJob::STATUS_SCHEDULED, 'job_type' => 'delivery']);
        $buyerOrder = BuyerOrder::create([
            'buyer_id' => $buyer->id, 'cooperative_id' => $coop->id,
            'reference' => 'ORD-'.Str::random(8), 'status' => BuyerOrder::STATUS_ACCEPTED,
            'total_kg' => 100, 'total_amount' => 5000,
        ]);
        $stop = HaulJobStop::factory()->create([
            'haul_job_id' => $haulJob->id,
            'haul_request_id' => null,
            'buyer_order_id' => $buyerOrder->id,
            'planned_arrival_at' => now()->addHours(3),
        ]);

        $response = $this->actingAs($buyer)->get(route('buyer.orders.track', $buyerOrder));

        $response->assertOk();
        $response->assertSee($stop->planned_arrival_at->format('M d, g:i A'));
    }

    public function test_tracking_page_does_not_crash_when_no_job_scheduled_yet(): void
    {
        $coop = $this->coop();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $haulRequest = HaulRequest::factory()->create(['farmer_id' => $farmer->id, 'cooperative_id' => $coop->id]);

        $response = $this->actingAs($farmer)->get(route('farmer.haul-requests.track', $haulRequest));

        $response->assertOk();
        $response->assertSee('Not scheduled yet');
    }
}
