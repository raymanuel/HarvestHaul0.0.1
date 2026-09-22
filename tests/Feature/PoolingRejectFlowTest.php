<?php

namespace Tests\Feature;

use App\Actions\ConfirmPoolingPlanAction;
use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\Negotiation;
use App\Models\NegotiationStatus;
use App\Models\Notification;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PoolingRejectFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createLogisticsUser(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name'       => 'Coop Logistics',
            'business_permit_no' => 'BL-12345',
            'phone'              => '09123456789',
            'is_verified'        => true,
            'logistics_type'     => 'cooperative',
        ]);
        return $user;
    }

    private function createCoopFarmer(): User
    {
        $user = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $user->farmerProfile()->create([
            'phone'            => '09123456789',
            'farm_location'    => 'Test Farm',
            'is_verified'      => true,
            'latitude'         => 7.0,
            'longitude'        => 125.5,
            'affiliation_type' => 'cooperative',
        ]);
        return $user;
    }

    private function createHarvest(User $farmer, int $qty, string $cropName = 'Rice'): Harvest
    {
        $category = CropCategory::firstOrCreate(['name' => 'Grains'], ['status' => 'active']);
        $crop = Crop::firstOrCreate(['name' => $cropName], ['crop_category_id' => $category->id, 'status' => 'active']);
        $variety = CropVariety::firstOrCreate(['crop_id' => $crop->id, 'name' => 'IR64'], ['status' => 'active']);

        return Harvest::create([
            'user_id'               => $farmer->id,
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'crop_category_id'      => $category->id,
            'crop_type'             => $crop->name,
            'variety'               => $variety->name,
            'quantity_kg'           => $qty,
            'remaining_quantity_kg' => $qty,
            'unit'                  => 'kg',
            'status'                => 'assigned',
            'destination_address'   => 'Test Market',
            'destination_latitude'  => 8.0,
            'destination_longitude' => 126.0,
            'latitude'              => 7.0,
            'longitude'             => 125.5,
        ]);
    }

    private function createJob(User $logisticsUser, array $extra = []): PoolingJob
    {
        $truck = Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_name'           => 'Test Truck',
            'plate_number'         => 'ABC-1234',
            'capacity_kg'          => 5000,
            'status'               => 'available',
            'vehicle_type'         => 'truck',
        ]);

        return PoolingJob::create(array_merge([
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_id'             => $truck->id,
            'status'               => PoolingJobStatus::PENDING,
            'total_kg'             => 0,
            'truck_capacity_kg'    => 5000,
            'farm_count'           => 0,
        ], $extra));
    }

    public function test_reject_keeps_pivot_status_rejected(): void
    {
        $logistics = $this->createLogisticsUser();
        $farmerA   = $this->createCoopFarmer();
        $farmerB   = $this->createCoopFarmer();

        $job = $this->createJob($logistics);

        $harvestA = $this->createHarvest($farmerA, 300, 'Rice');
        $harvestB = $this->createHarvest($farmerB, 200, 'Corn');

        $job->harvests()->attach($harvestA->id, ['quantity_kg' => 300, 'cost_share' => 1500, 'status' => 'pending', 'pickup_order' => 1]);
        $job->harvests()->attach($harvestB->id, ['quantity_kg' => 200, 'cost_share' => 1600, 'status' => 'pending', 'pickup_order' => 2]);
        $job->update(['total_kg' => 500, 'farm_count' => 2]);

        $this->actingAs($farmerA)->post(route('pooling.reject', $job->id))->assertRedirect();

        $job->refresh();
        $job->load('harvests');

        // A's pivot is rejected but still attached
        $pivotA = $job->harvests->firstWhere('id', $harvestA->id)->pivot;
        $this->assertEquals('rejected', $pivotA->status, 'Rejected pivot should be retained with status rejected');

        // B's pivot untouched
        $pivotB = $job->harvests->firstWhere('id', $harvestB->id)->pivot;
        $this->assertEquals('pending', $pivotB->status);

        // Job still pending (B hasn't settled)
        $this->assertEquals(PoolingJobStatus::PENDING, $job->status);

        // pendingForUser(A) should be false — all A's pivots are rejected (not pending)
        $hasPendingA = $job->harvests->where('user_id', $farmerA->id)->contains('pivot.status', 'pending');
        $this->assertFalse($hasPendingA, 'Farmer A should have no pending pivots');
    }

    public function test_reject_all_cancels_and_releases_truck(): void
    {
        $logistics = $this->createLogisticsUser();
        $farmer    = $this->createCoopFarmer();

        $truck = Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'truck_name'           => 'Solo Truck',
            'plate_number'         => 'XYZ-9999',
            'capacity_kg'          => 5000,
            'status'               => 'on_route',
            'vehicle_type'         => 'truck',
        ]);

        $job = PoolingJob::create([
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'truck_id'             => $truck->id,
            'status'               => PoolingJobStatus::PENDING,
            'total_kg'             => 200,
            'truck_capacity_kg'    => 5000,
            'farm_count'           => 1,
        ]);

        $harvest = $this->createHarvest($farmer, 200, 'Banana');
        $job->harvests()->attach($harvest->id, [
            'quantity_kg' => 200, 'cost_share' => 800,
            'status' => 'pending', 'pickup_order' => 1,
        ]);

        $this->actingAs($farmer)->post(route('pooling.reject', $job->id))->assertRedirect();

        $job->refresh();
        $this->assertEquals(PoolingJobStatus::CANCELLED, $job->status);

        $truck->refresh();
        $this->assertEquals('available', $truck->status, 'Truck should be released');
    }

    public function test_reject_last_pending_farmer_per_farmer_rates_auto_confirms(): void
    {
        Mail::fake();

        $logistics = $this->createLogisticsUser();
        $farmerA   = $this->createCoopFarmer();
        $farmerB   = $this->createCoopFarmer();

        $job = $this->createJob($logistics);

        $harvestA = $this->createHarvest($farmerA, 300, 'Rice');
        $harvestB = $this->createHarvest($farmerB, 200, 'Corn');

        Negotiation::factory()->create([
            'harvest_id'          => $harvestA->id,
            'buyer_id'            => $logistics->id,
            'farmer_id'           => $farmerA->id,
            'status'              => NegotiationStatus::COMPLETED,
            'hauling_rate_per_kg' => 5.00,
        ]);
        Negotiation::factory()->create([
            'harvest_id'          => $harvestB->id,
            'buyer_id'            => $logistics->id,
            'farmer_id'           => $farmerB->id,
            'status'              => NegotiationStatus::COMPLETED,
            'hauling_rate_per_kg' => 8.00,
        ]);

        $job->harvests()->attach($harvestA->id, ['quantity_kg' => 300, 'cost_share' => 0, 'status' => 'accepted', 'pickup_order' => 1]);
        $job->harvests()->attach($harvestB->id, ['quantity_kg' => 200, 'cost_share' => 0, 'status' => 'pending', 'pickup_order' => 2]);
        $job->update(['total_kg' => 500, 'farm_count' => 2]);

        // Recalculate so cost_shares are set
        app(ConfirmPoolingPlanAction::class)->recalculateCostShares($job);

        // Farmer A accepted, B rejects → all remaining settled → auto-confirm
        $this->actingAs($farmerB)->post(route('pooling.reject', $job->id))->assertRedirect();

        $job->refresh();
        $this->assertEquals(PoolingJobStatus::CONFIRMED, $job->status, 'Job should auto-confirm when all remaining farmers settled');
        $this->assertNotNull($job->confirmed_at);

        // Invoice generated
        $this->assertDatabaseHas('invoices', ['pooling_job_id' => $job->id]);

        // Logistics notified
        $this->assertDatabaseHas('notifications', [
            'user_id' => $logistics->id,
            'title'   => 'Proposal Confirmed',
        ]);

        // Harvests marked ASSIGNED
        $harvestA->refresh();
        $this->assertEquals(HarvestStatus::ASSIGNED, $harvestA->status);
    }

    public function test_reject_flat_rate_resets_others_to_pending_and_notifies_reapproval(): void
    {
        $logistics = $this->createLogisticsUser();
        $farmerA   = $this->createCoopFarmer();
        $farmerB   = $this->createCoopFarmer();

        // Flat-rate job (no per-farmer hauling rates)
        $job = $this->createJob($logistics, ['hauling_rate_per_kg' => 10.00]);

        $harvestA = $this->createHarvest($farmerA, 300, 'Rice');
        $harvestB = $this->createHarvest($farmerB, 200, 'Corn');

        $job->harvests()->attach($harvestA->id, ['quantity_kg' => 300, 'cost_share' => 1800, 'status' => 'accepted', 'pickup_order' => 1]);
        $job->harvests()->attach($harvestB->id, ['quantity_kg' => 200, 'cost_share' => 1200, 'status' => 'pending', 'pickup_order' => 2]);
        $job->update(['total_kg' => 500, 'farm_count' => 2]);

        $this->actingAs($farmerB)->post(route('pooling.reject', $job->id))->assertRedirect();

        $job->refresh();
        $job->load('harvests');
        $this->assertEquals(PoolingJobStatus::PENDING, $job->status);

        // A's pivot reset to pending
        $pivotA = $job->harvests->firstWhere('id', $harvestA->id)->pivot;
        $this->assertEquals('pending', $pivotA->status, 'Under flat rate, remaining accepted pivot should reset to pending');

        // Re-approval notification for A
        $this->assertDatabaseHas('notifications', [
            'user_id' => $farmerA->id,
            'title'   => 'Cost Shares Recalculated — Re-approval Required',
        ]);
    }

    public function test_driver_route_excludes_rejected_stops(): void
    {
        Mail::fake();

        $logistics = $this->createLogisticsUser();
        $farmerA   = $this->createCoopFarmer();
        $farmerB   = $this->createCoopFarmer();
        $driver    = User::factory()->driver()->create(['email_verified_at' => now()]);

        $truck = Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'truck_name'           => 'Driver Truck',
            'plate_number'         => 'DRV-1234',
            'capacity_kg'          => 5000,
            'status'               => 'available',
            'vehicle_type'         => 'truck',
        ]);

        $job = PoolingJob::create([
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'truck_id'             => $truck->id,
            'driver_id'            => $driver->id,
            'status'               => PoolingJobStatus::CONFIRMED,
            'total_kg'             => 500,
            'truck_capacity_kg'    => 5000,
            'farm_count'           => 2,
            'accepted_at'          => now(),
            'start_latitude'       => 7.0,
            'start_longitude'      => 125.5,
            'end_latitude'         => 8.0,
            'end_longitude'        => 126.0,
        ]);

        $harvestA = $this->createHarvest($farmerA, 300, 'Rice');
        $harvestB = $this->createHarvest($farmerB, 200, 'Corn');

        $job->harvests()->attach($harvestA->id, ['quantity_kg' => 300, 'cost_share' => 1500, 'status' => 'accepted', 'pickup_order' => 1]);
        $job->harvests()->attach($harvestB->id, ['quantity_kg' => 200, 'cost_share' => 1600, 'status' => 'rejected', 'pickup_order' => 2]);

        $response = $this->actingAs($driver)->get(route('driver.jobs.show', $job->id));
        $response->assertStatus(200);

        $view = $response->getOriginalContent();
        $viewJob = $view->gatherData()['job'];

        // Rejected pivot should not be in the job's harvests for the view
        $visibleHarvests = $viewJob->harvests;
        $this->assertCount(1, $visibleHarvests, 'Driver route should only show non-rejected stops');
        $this->assertEquals($harvestA->id, $visibleHarvests->first()->id);
    }

    public function test_update_status_skips_rejected_in_reset_and_delivery_check(): void
    {
        Mail::fake();

        $logistics = $this->createLogisticsUser();
        $farmerA   = $this->createCoopFarmer();
        $farmerB   = $this->createCoopFarmer();
        $driver    = User::factory()->driver()->create(['email_verified_at' => now()]);

        $truck = Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'truck_name'           => 'Driver Truck',
            'plate_number'         => 'DRV-1234',
            'capacity_kg'          => 5000,
            'status'               => 'available',
            'vehicle_type'         => 'truck',
        ]);

        $job = PoolingJob::create([
            'logistics_profile_id' => $logistics->logisticsProfile->id,
            'truck_id'             => $truck->id,
            'driver_id'            => $driver->id,
            'status'               => PoolingJobStatus::CONFIRMED,
            'total_kg'             => 500,
            'truck_capacity_kg'    => 5000,
            'farm_count'           => 2,
            'accepted_at'          => now(),
        ]);

        $harvestA = $this->createHarvest($farmerA, 300, 'Rice');
        $harvestB = $this->createHarvest($farmerB, 200, 'Corn');

        $job->harvests()->attach($harvestA->id, ['quantity_kg' => 300, 'cost_share' => 1500, 'status' => 'accepted', 'pickup_order' => 1]);
        $job->harvests()->attach($harvestB->id, ['quantity_kg' => 200, 'cost_share' => 1600, 'status' => 'rejected', 'pickup_order' => 2]);

        // Start the job (IN_PROGRESS)
        $this->actingAs($driver)->patch(route('driver.jobs.status', $job->id));

        $job->refresh();
        $job->load('harvests');
        $this->assertEquals(PoolingJobStatus::IN_PROGRESS, $job->status);

        // A's pivot reset to 'assigned' for the stop chain
        $pivotA = $job->harvests->firstWhere('id', $harvestA->id)->pivot;
        $this->assertEquals('assigned', $pivotA->status);

        // B's rejected pivot stays rejected — not reset
        $pivotB = $job->harvests->firstWhere('id', $harvestB->id)->pivot;
        $this->assertEquals('rejected', $pivotB->status, 'Rejected pivot must not be reset to assigned');
    }

    public function test_invoice_and_cost_ledger_exclude_rejected(): void
    {
        Mail::fake();

        $logistics = $this->createLogisticsUser();
        $farmerA   = $this->createCoopFarmer();
        $farmerB   = $this->createCoopFarmer();

        $job = $this->createJob($logistics);

        $harvestA = $this->createHarvest($farmerA, 300, 'Rice');
        $harvestB = $this->createHarvest($farmerB, 200, 'Corn');

        Negotiation::factory()->create([
            'harvest_id'          => $harvestA->id,
            'buyer_id'            => $logistics->id,
            'farmer_id'           => $farmerA->id,
            'status'              => NegotiationStatus::COMPLETED,
            'hauling_rate_per_kg' => 5.00,
        ]);
        Negotiation::factory()->create([
            'harvest_id'          => $harvestB->id,
            'buyer_id'            => $logistics->id,
            'farmer_id'           => $farmerB->id,
            'status'              => NegotiationStatus::COMPLETED,
            'hauling_rate_per_kg' => 8.00,
        ]);

        // A accepted, B rejected → auto-confirm
        $job->harvests()->attach($harvestA->id, ['quantity_kg' => 300, 'cost_share' => 1500, 'status' => 'accepted', 'pickup_order' => 1]);
        $job->harvests()->attach($harvestB->id, ['quantity_kg' => 200, 'cost_share' => 1600, 'status' => 'rejected', 'pickup_order' => 2]);
        $job->total_kg = 500;
        $job->farm_count = 2;
        $job->save();

        app(ConfirmPoolingPlanAction::class)->recalculateCostShares($job);

        // Verify the service level
        $invoiceService = app(\App\Services\InvoiceService::class);
        $invoice = $invoiceService->generateInvoice($job);

        $job->refresh();
        $job->load('harvests');

        // Invoice total should NOT include rejected cost_share
        $activeShares = $job->harvests->filter(fn($h) => $h->pivot->status !== 'rejected')
            ->sum(fn($h) => (float) ($h->pivot->cost_share ?? 0));
        $this->assertEqualsWithDelta($activeShares, (float) $invoice->total_amount, 0.01,
            'Invoice total must exclude rejected pivots');

        // Cost ledger entries should exclude rejected
        $response = $this->actingAs($logistics)->get(route('pooling.cost-ledger', $job->id));
        $response->assertStatus(200);

        $view = $response->getOriginalContent();
        $ledgerEntries = $view->gatherData()['ledgerEntries'];
        $this->assertCount(1, $ledgerEntries, 'Cost ledger must exclude rejected harvests');
        $this->assertEquals($harvestA->id, $ledgerEntries->first()['harvest_id']);
    }

    public function test_farmer_view_hides_buttons_once_settled(): void
    {
        $logistics = $this->createLogisticsUser();
        $farmerA   = $this->createCoopFarmer();
        $farmerB   = $this->createCoopFarmer();

        $job = $this->createJob($logistics);

        $harvestA = $this->createHarvest($farmerA, 300, 'Rice');
        $harvestB = $this->createHarvest($farmerA, 150, 'Corn');

        $job->harvests()->attach($harvestA->id, ['quantity_kg' => 300, 'cost_share' => 1500, 'status' => 'accepted', 'pickup_order' => 1]);
        $job->harvests()->attach($harvestB->id, ['quantity_kg' => 150, 'cost_share' => 750, 'status' => 'rejected', 'pickup_order' => 2]);
        $job->update(['total_kg' => 450, 'farm_count' => 1]);

        $response = $this->actingAs($farmerA)->get(route('farmer.proposals'));
        $response->assertStatus(200);

        $html = $response->content();
        // No accept/reject forms should appear — all pivots are settled
        $this->assertStringNotContainsString('pooling/accept', $html, 'Settled farmer should not see Accept form');
        $this->assertStringNotContainsString('pooling/reject', $html, 'Settled farmer should not see Reject form');
        // Settled message should appear
        $this->assertStringContainsString('Rejected', $html, 'Should show rejected status');
    }

    public function test_recalculateCostShares_skips_rejected_pivots(): void
    {
        $logistics = $this->createLogisticsUser();
        $farmerA   = $this->createCoopFarmer();
        $farmerB   = $this->createCoopFarmer();

        $job = $this->createJob($logistics);

        $harvestA = $this->createHarvest($farmerA, 300, 'Rice');
        $harvestB = $this->createHarvest($farmerB, 200, 'Corn');

        Negotiation::factory()->create([
            'harvest_id'          => $harvestA->id,
            'buyer_id'            => $logistics->id,
            'farmer_id'           => $farmerA->id,
            'status'              => NegotiationStatus::COMPLETED,
            'hauling_rate_per_kg' => 5.00,
        ]);
        Negotiation::factory()->create([
            'harvest_id'          => $harvestB->id,
            'buyer_id'            => $logistics->id,
            'farmer_id'           => $farmerB->id,
            'status'              => NegotiationStatus::COMPLETED,
            'hauling_rate_per_kg' => 8.00,
        ]);

        $job->harvests()->attach($harvestA->id, ['quantity_kg' => 300, 'cost_share' => 0, 'status' => 'accepted', 'pickup_order' => 1]);
        $job->harvests()->attach($harvestB->id, ['quantity_kg' => 200, 'cost_share' => 0, 'status' => 'rejected', 'pickup_order' => 2]);

        app(ConfirmPoolingPlanAction::class)->recalculateCostShares($job);

        $shareA = (float) $job->harvests()->where('harvests.id', $harvestA->id)->first()->pivot->cost_share;
        $shareB = (float) $job->harvests()->where('harvests.id', $harvestB->id)->first()->pivot->cost_share;

        // A gets its rate × kg
        $this->assertEqualsWithDelta(1500.00, $shareA, 0.01, 'Accepted farmer share should be recalculated normally');
        // B's rejected pivot should have cost_share 0 or unchanged — NOT recalculated with a positive value
        $this->assertEqualsWithDelta(0.0, $shareB, 0.01, 'Rejected pivot cost_share should not be recalculated');
    }
}
