<?php

namespace Tests\Feature;

use App\Actions\ConfirmPoolingPlanAction;
use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\Harvest;
use App\Models\Negotiation;
use App\Models\NegotiationStatus;
use App\Models\PoolingJob;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerFarmerHaulingRateTest extends TestCase
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
            'status'               => 'pending',
            'total_kg'             => 0,
            'truck_capacity_kg'    => 5000,
            'farm_count'           => 0,
        ], $extra));
    }

    public function test_cost_share_is_per_farmer_rate_times_kg(): void
    {
        $logistics = $this->createLogisticsUser();
        $farmerA   = $this->createCoopFarmer();
        $farmerB   = $this->createCoopFarmer();

        $harvestA = $this->createHarvest($farmerA, 300);
        $harvestB = $this->createHarvest($farmerB, 200);

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

        $job = $this->createJob($logistics);
        $job->harvests()->attach($harvestA->id, ['quantity_kg' => 300, 'cost_share' => 0, 'status' => 'pending', 'pickup_order' => 1]);
        $job->harvests()->attach($harvestB->id, ['quantity_kg' => 200, 'cost_share' => 0, 'status' => 'pending', 'pickup_order' => 2]);

        app(ConfirmPoolingPlanAction::class)->recalculateCostShares($job);

        $shareA = $job->harvests()->where('harvests.id', $harvestA->id)->first()->pivot->cost_share;
        $shareB = $job->harvests()->where('harvests.id', $harvestB->id)->first()->pivot->cost_share;

        $this->assertEqualsWithDelta(1500.00, (float) $shareA, 0.01, 'Farmer A share should be 5 x 300 = 1500');
        $this->assertEqualsWithDelta(1600.00, (float) $shareB, 0.01, 'Farmer B share should be 8 x 200 = 1600');
    }

    public function test_rejection_leaves_other_farmers_share_unchanged(): void
    {
        $logistics = $this->createLogisticsUser();
        $farmerA   = $this->createCoopFarmer();
        $farmerB   = $this->createCoopFarmer();

        $harvestA = $this->createHarvest($farmerA, 300);
        $harvestB = $this->createHarvest($farmerB, 200);

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

        $job = $this->createJob($logistics);
        $job->harvests()->attach($harvestA->id, ['quantity_kg' => 300, 'cost_share' => 0, 'status' => 'pending', 'pickup_order' => 1]);
        $job->harvests()->attach($harvestB->id, ['quantity_kg' => 200, 'cost_share' => 0, 'status' => 'pending', 'pickup_order' => 2]);

        $action = app(ConfirmPoolingPlanAction::class);
        $action->recalculateCostShares($job);

        $shareABefore = (float) $job->harvests()->where('harvests.id', $harvestA->id)->first()->pivot->cost_share;

        // Farmer B rejects (detached from route), leaving only farmer A.
        $job->harvests()->detach($harvestB->id);
        $job->load('harvests');

        $this->assertTrue($action::usesPerFarmerRates($job), 'Job should still use per-farmer rates after one rejection');

        $action->recalculateCostShares($job);

        $shareAAfterDetach = $job->harvests()->where('harvests.id', $harvestA->id)->first()->pivot->cost_share;

        $this->assertEqualsWithDelta(1500.00, (float) $shareAAfterDetach, 0.01, 'Farmer A share unchanged by farmer B rejection');
        $this->assertEqualsWithDelta((float) $shareABefore, (float) $shareAAfterDetach, 0.01, 'No cascade: share equals pre-rejection value');
    }

    public function test_farmer_invoice_email_is_scoped_to_own_share_only(): void
    {
        $logistics = $this->createLogisticsUser();
        $farmerA   = $this->createCoopFarmer();
        $farmerB   = $this->createCoopFarmer();

        $harvestA = $this->createHarvest($farmerA, 300);
        $harvestB = $this->createHarvest($farmerB, 200);

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

        $job = $this->createJob($logistics);
        $job->total_kg = 500;
        $job->farm_count = 2;
        $job->save();
        $job->harvests()->attach($harvestA->id, ['quantity_kg' => 300, 'cost_share' => 1500.00, 'status' => 'accepted', 'pickup_order' => 1]);
        $job->harvests()->attach($harvestB->id, ['quantity_kg' => 200, 'cost_share' => 1600.00, 'status' => 'accepted', 'pickup_order' => 2]);

        \Illuminate\Support\Facades\Mail::fake();

        $this->assertEquals(500, (float) $job->total_kg, 'job total_kg should be 500');
        $this->assertEquals(2, (int) $job->farm_count, 'job farm_count should be 2');

        $invoice = app(\App\Services\InvoiceService::class)->generateInvoice($job);

        $service = app(\App\Services\InvoiceService::class);
        $job->load(['harvests.farmer']);

        $htmlForA = $service->renderInvoiceHtml($job, $invoice, $farmerA->id);
        $this->assertStringContainsString($farmerA->name, $htmlForA, 'Farmer A scoped invoice HTML should contain farmer A name');
        $this->assertStringNotContainsString($farmerB->name, $htmlForA, 'Farmer A scoped invoice HTML must NOT contain farmer B name');

        $scopedPdf = storage_path("app/private/invoices/{$invoice->invoice_number}-farmer-{$farmerA->id}.pdf");
        $this->assertFileExists($scopedPdf, 'Farmer-specific scoped PDF should be generated');

        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\InvoiceMail::class, function ($mail) use ($farmerA) {
            return $mail->hasTo($farmerA->email) && $mail->farmerShare === 1500.0;
        });
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\InvoiceMail::class, function ($mail) use ($farmerB) {
            return $mail->hasTo($farmerB->email) && $mail->farmerShare === 1600.0;
        });
    }
}
