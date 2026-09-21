<?php

namespace Tests\Feature;

use App\Models\BuyerOrder;
use App\Models\BuyerOrderItem;
use App\Models\BuyerProfile;
use App\Models\Cooperative;
use App\Models\Crop;
use App\Models\CropAvailability;
use App\Models\CropGrade;
use App\Models\HaulJob;
use App\Models\HaulRequest;
use App\Models\ReceivingRecord;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoopBuyerOrderReviewTest extends TestCase
{
    use RefreshDatabase;

    private function cooperative(string $name = 'GenSan AgCoop'): Cooperative
    {
        return Cooperative::create([
            'name' => $name, 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => strtolower(str_replace(' ', '', $name)).'@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
    }

    private function coopAdmin(Cooperative $coop): User
    {
        return User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id, 'email_verified_at' => now()]);
    }

    private function orderWithListing(Cooperative $coop, float $listingKg, float $orderKg, array $orderOverrides = []): array
    {
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        BuyerProfile::factory()->approved()->for($buyer)->create();
        $crop = Crop::factory()->create();
        $grade = CropGrade::factory()->create();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id]);
        $req = HaulRequest::factory()->create(['cooperative_id' => $coop->id, 'crop_id' => $crop->id, 'status' => HaulRequest::STATUS_COMPLETED]);
        $job = HaulJob::create([
            'haul_request_id' => null, 'cooperative_id' => $coop->id,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $truck->id,
            'pickup_date' => today(), 'status' => HaulJob::STATUS_COMPLETED,
        ]);
        $record = ReceivingRecord::create([
            'haul_job_id' => $job->id, 'haul_request_id' => $req->id, 'cooperative_id' => $coop->id,
            'farmer_id' => $farmer->id, 'crop_id' => $crop->id, 'crop_grade_id' => $grade->id,
            'actual_sacks' => 78, 'actual_weight_kg' => $listingKg, 'recorded_by' => $driver->id,
            'status' => ReceivingRecord::STATUS_CONFIRMED, 'buying_price_per_kg' => 18, 'total_amount' => $listingKg * 18,
        ]);

        $listing = CropAvailability::create([
            'cooperative_id' => $coop->id, 'receiving_record_id' => $record->id,
            'crop_id' => $crop->id, 'crop_grade_id' => $grade->id,
            'quantity_kg' => $listingKg, 'selling_price_per_kg' => 25, 'status' => CropAvailability::STATUS_AVAILABLE,
        ]);

        $order = BuyerOrder::create(array_merge([
            'buyer_id' => $buyer->id, 'cooperative_id' => $coop->id, 'reference' => 'ORD-TEST01',
            'status' => BuyerOrder::STATUS_SUBMITTED, 'total_kg' => $orderKg, 'total_amount' => $orderKg * 25,
            'delivery_address' => 'Test address',
        ], $orderOverrides));

        BuyerOrderItem::create([
            'buyer_order_id' => $order->id, 'crop_availability_id' => $listing->id,
            'crop_id' => $crop->id, 'crop_grade_id' => $grade->id,
            'quantity_kg' => $orderKg, 'rate_per_kg' => 25, 'subtotal' => $orderKg * 25,
        ]);

        return [$order, $listing, $buyer];
    }

    public function test_opening_order_moves_submitted_to_under_review(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        [$order] = $this->orderWithListing($coop, 4500, 2000);

        $this->actingAs($admin)->get(route('coop.buyer-orders.show', $order))->assertOk();

        $this->assertEquals(BuyerOrder::STATUS_UNDER_REVIEW, $order->fresh()->status);
    }

    public function test_accept_reserves_stock_and_marks_sold_out_when_depleted(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        [$order, $listing] = $this->orderWithListing($coop, 2000, 2000);

        $response = $this->actingAs($admin)->post(route('coop.buyer-orders.accept', $order));

        $response->assertRedirect();
        $order->refresh();
        $listing->refresh();
        $this->assertEquals(BuyerOrder::STATUS_ACCEPTED, $order->status);
        $this->assertEquals(2000.00, (float) $listing->sold_kg);
        $this->assertEquals(0.0, $listing->remaining_kg);
        $this->assertEquals(CropAvailability::STATUS_SOLD_OUT, $listing->status);
    }

    public function test_accept_partial_quantity_keeps_listing_available(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        [$order, $listing] = $this->orderWithListing($coop, 4500, 2000);

        $this->actingAs($admin)->post(route('coop.buyer-orders.accept', $order));

        $listing->refresh();
        $this->assertEquals(2500.00, $listing->remaining_kg);
        $this->assertEquals(CropAvailability::STATUS_AVAILABLE, $listing->status);
    }

    public function test_accept_blocked_when_stock_insufficient(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        [$order, $listing] = $this->orderWithListing($coop, 500, 2000);

        $response = $this->actingAs($admin)->post(route('coop.buyer-orders.accept', $order));

        $response->assertSessionHasErrors('order');
        $this->assertEquals(BuyerOrder::STATUS_SUBMITTED, $order->fresh()->status);
        $this->assertEquals(0.0, (float) $listing->fresh()->sold_kg);
    }

    public function test_reject_requires_reason_and_does_not_touch_inventory(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        [$order, $listing] = $this->orderWithListing($coop, 4500, 2000);

        $blocked = $this->actingAs($admin)->post(route('coop.buyer-orders.reject', $order));
        $blocked->assertSessionHasErrors('rejection_reason');

        $response = $this->actingAs($admin)->post(route('coop.buyer-orders.reject', $order), [
            'rejection_reason' => 'Out of delivery range.',
        ]);
        $response->assertRedirect();
        $this->assertEquals(BuyerOrder::STATUS_REJECTED, $order->fresh()->status);
        $this->assertEquals(0.0, (float) $listing->fresh()->sold_kg);
    }

    public function test_cannot_decide_an_already_decided_order(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        [$order] = $this->orderWithListing($coop, 4500, 2000, ['status' => BuyerOrder::STATUS_ACCEPTED]);

        $response = $this->actingAs($admin)->post(route('coop.buyer-orders.accept', $order));

        $response->assertSessionHasErrors('order');
    }

    public function test_other_cooperative_cannot_review_order(): void
    {
        $coop = $this->cooperative('GenSan AgCoop');
        $otherCoop = $this->cooperative('Davao AgCoop');
        $otherAdmin = $this->coopAdmin($otherCoop);
        [$order] = $this->orderWithListing($coop, 4500, 2000);

        $response = $this->actingAs($otherAdmin)->get(route('coop.buyer-orders.show', $order));

        $response->assertForbidden();
    }
}
