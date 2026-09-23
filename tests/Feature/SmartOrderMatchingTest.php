<?php

namespace Tests\Feature;

use App\Models\BuyerProfile;
use App\Models\Cooperative;
use App\Models\Crop;
use App\Models\CropAvailability;
use App\Models\CropGrade;
use App\Models\HaulJob;
use App\Models\ReceivingRecord;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartOrderMatchingTest extends TestCase
{
    use RefreshDatabase;

    private function listing(Cooperative $coop, Crop $crop, array $overrides = []): CropAvailability
    {
        $grade = $overrides['crop_grade_id'] ?? null
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

    private function buyer(): User
    {
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        BuyerProfile::factory()->approved()->create(['user_id' => $buyer->id]);

        return $buyer;
    }

    public function test_browse_defaults_to_best_match_order(): void
    {
        $buyer = $this->buyer();
        $coop = Cooperative::factory()->create();
        $crop = Crop::factory()->create();
        $this->listing($coop, $crop);

        $response = $this->actingAs($buyer)->get(route('buyer.listings.index'));

        $response->assertOk();
        $response->assertViewHas('listings');
    }

    public function test_crop_filter_narrows_results(): void
    {
        $buyer = $this->buyer();
        $coop = Cooperative::factory()->create();
        $corn = Crop::factory()->create(['name' => 'Corn']);
        $rice = Crop::factory()->create(['name' => 'Rice']);
        $this->listing($coop, $corn);
        $this->listing($coop, $rice);

        $response = $this->actingAs($buyer)->get(route('buyer.listings.index', ['crop_id' => $corn->id]));

        $listings = $response->viewData('listings');
        $this->assertCount(1, $listings);
        $this->assertEquals($corn->id, $listings->first()->crop_id);
    }

    public function test_grade_filter_narrows_results(): void
    {
        $buyer = $this->buyer();
        $coop = Cooperative::factory()->create();
        $crop = Crop::factory()->create();
        $gradeA = CropGrade::factory()->create();
        $gradeB = CropGrade::factory()->create();
        $this->listing($coop, $crop, ['crop_grade_id' => $gradeA->id]);
        $this->listing($coop, $crop, ['crop_grade_id' => $gradeB->id]);

        $response = $this->actingAs($buyer)->get(route('buyer.listings.index', ['crop_grade_id' => $gradeA->id]));

        $listings = $response->viewData('listings');
        $this->assertCount(1, $listings);
        $this->assertEquals($gradeA->id, $listings->first()->crop_grade_id);
    }

    public function test_max_price_filter_narrows_results(): void
    {
        $buyer = $this->buyer();
        $coop = Cooperative::factory()->create();
        $crop = Crop::factory()->create();
        $this->listing($coop, $crop, ['selling_price_per_kg' => 15]);
        $this->listing($coop, $crop, ['selling_price_per_kg' => 30]);

        $response = $this->actingAs($buyer)->get(route('buyer.listings.index', ['max_price' => 20]));

        $listings = $response->viewData('listings');
        $this->assertCount(1, $listings);
        $this->assertEquals(15, (float) $listings->first()->selling_price_per_kg);
    }

    public function test_min_kg_drops_undersized_listings(): void
    {
        $buyer = $this->buyer();
        $coop = Cooperative::factory()->create();
        $crop = Crop::factory()->create();
        $this->listing($coop, $crop, ['quantity_kg' => 500]);
        $this->listing($coop, $crop, ['quantity_kg' => 3000]);

        $response = $this->actingAs($buyer)->get(route('buyer.listings.index', ['min_kg' => 2000]));

        $listings = $response->viewData('listings');
        $this->assertCount(1, $listings);
        $this->assertEquals(3000, (float) $listings->first()->quantity_kg);
    }

    public function test_sort_cheapest_reorders_results(): void
    {
        $buyer = $this->buyer();
        $coop = Cooperative::factory()->create();
        $crop = Crop::factory()->create();
        $this->listing($coop, $crop, ['selling_price_per_kg' => 30]);
        $this->listing($coop, $crop, ['selling_price_per_kg' => 10]);

        $response = $this->actingAs($buyer)->get(route('buyer.listings.index', ['sort' => 'cheapest']));

        $listings = $response->viewData('listings');
        $this->assertEquals(10, (float) $listings->first()->selling_price_per_kg);
    }

    public function test_pagination_preserves_query_string(): void
    {
        $buyer = $this->buyer();
        $coop = Cooperative::factory()->create();
        $crop = Crop::factory()->create();
        $grade = CropGrade::factory()->create();
        for ($i = 0; $i < 25; $i++) {
            $this->listing($coop, $crop, ['crop_grade_id' => $grade->id]);
        }

        $response = $this->actingAs($buyer)->get(route('buyer.listings.index', ['sort' => 'cheapest']));

        $response->assertOk();
        $response->assertSee('sort=cheapest', false);
    }

    public function test_non_buyer_role_forbidden(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);

        $response = $this->actingAs($farmer)->get(route('buyer.listings.index'));

        $response->assertForbidden();
    }
}
