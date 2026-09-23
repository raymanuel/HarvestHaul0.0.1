<?php

namespace Tests\Unit\Services;

use App\Models\BuyerProfile;
use App\Models\Cooperative;
use App\Models\Crop;
use App\Models\CropAvailability;
use App\Models\CropGrade;
use App\Models\HaulJob;
use App\Models\ReceivingRecord;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Matching\OrderMatchingService;
use App\Services\Routing\HaversineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class OrderMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderMatchingService $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new OrderMatchingService(new HaversineService());
    }

    private function cooperative(?float $lat = null, ?float $lng = null): Cooperative
    {
        return Cooperative::factory()->create(['latitude' => $lat, 'longitude' => $lng]);
    }

    private function buyerProfile(?float $lat, ?float $lng): BuyerProfile
    {
        return BuyerProfile::factory()->create(['latitude' => $lat, 'longitude' => $lng]);
    }

    private function listing(Cooperative $coop, array $overrides = []): CropAvailability
    {
        $crop = Crop::factory()->create();
        $grade = isset($overrides['crop_grade_id'])
            ? CropGrade::find($overrides['crop_grade_id'])
            : CropGrade::factory()->create();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $recorder = User::factory()->create(['role' => UserRole::FIELD_RECEIVING->value, 'cooperative_id' => $coop->id]);
        $job = HaulJob::create(['cooperative_id' => $coop->id, 'job_type' => HaulJob::JOB_TYPE_PICKUP, 'pickup_date' => today(), 'status' => HaulJob::STATUS_SCHEDULED]);
        $record = ReceivingRecord::create([
            'haul_job_id' => $job->id, 'cooperative_id' => $coop->id, 'farmer_id' => $farmer->id,
            'crop_id' => $crop->id, 'crop_grade_id' => $grade->id, 'actual_weight_kg' => 1000, 'buying_price_per_kg' => 15,
            'total_amount' => 15000, 'recorded_by' => $recorder->id,
            'status' => ReceivingRecord::STATUS_CONFIRMED,
        ]);

        return CropAvailability::create(array_merge([
            'cooperative_id' => $coop->id,
            'receiving_record_id' => $record->id,
            'crop_id' => $crop->id,
            'quantity_kg' => 1000,
            'sold_kg' => 0,
            'selling_price_per_kg' => 20,
            'status' => CropAvailability::STATUS_AVAILABLE,
        ], $overrides));
    }

    public function test_closer_cooperative_outranks_farther_one(): void
    {
        $buyer = $this->buyerProfile(14.5995, 120.9842); // Manila
        $near = $this->cooperative(14.6091, 121.0223); // ~5 km away
        $far = $this->cooperative(10.3157, 123.8854); // Cebu, far

        $listings = collect([
            $this->listing($far)->load('cooperative', 'cropGrade'),
            $this->listing($near)->load('cooperative', 'cropGrade'),
        ]);

        $ranked = $this->matcher->rank($listings, $buyer);

        $this->assertEquals($near->id, $ranked->first()->cooperative_id);
    }

    public function test_listing_covering_requested_quantity_outranks_one_that_cannot(): void
    {
        $coop = $this->cooperative();

        $listings = collect([
            $this->listing($coop, ['quantity_kg' => 500, 'sold_kg' => 0])->load('cooperative', 'cropGrade'),
            $this->listing($coop, ['quantity_kg' => 3000, 'sold_kg' => 0])->load('cooperative', 'cropGrade'),
        ]);

        $ranked = $this->matcher->rank($listings, null, ['min_kg' => 2000]);

        $this->assertEquals(3000, (float) $ranked->first()->quantity_kg);
    }

    public function test_better_grade_wins_when_distance_and_quantity_tie(): void
    {
        $coop = $this->cooperative();
        $gradeA = CropGrade::factory()->create(['sort_order' => 1, 'is_active' => true]);
        $gradeC = CropGrade::factory()->create(['sort_order' => 3, 'is_active' => true]);

        $listings = collect([
            $this->listing($coop, ['crop_grade_id' => $gradeC->id])->load('cooperative', 'cropGrade'),
            $this->listing($coop, ['crop_grade_id' => $gradeA->id])->load('cooperative', 'cropGrade'),
        ]);

        $ranked = $this->matcher->rank($listings, null);

        $this->assertEquals($gradeA->id, $ranked->first()->crop_grade_id);
    }

    public function test_buyer_without_saved_location_still_gets_a_score(): void
    {
        $coop = $this->cooperative();
        $listings = collect([$this->listing($coop)->load('cooperative', 'cropGrade')]);

        $ranked = $this->matcher->rank($listings, null);

        $this->assertNotNull($ranked->first()->match_score);
        $this->assertNull($ranked->first()->distance_km);
    }

    public function test_identical_listings_score_identically(): void
    {
        $coop = $this->cooperative();
        $listings = collect([
            $this->listing($coop, ['quantity_kg' => 1000])->load('cooperative', 'cropGrade'),
            $this->listing($coop, ['quantity_kg' => 1000])->load('cooperative', 'cropGrade'),
        ]);

        $ranked = $this->matcher->rank($listings, null);

        $this->assertEquals($ranked->first()->match_score, $ranked->last()->match_score);
    }
}
