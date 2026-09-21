<?php

namespace Tests\Feature;

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

class BuyerOrderTest extends TestCase
{
    use RefreshDatabase;

    private function cooperative(): Cooperative
    {
        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
    }

    private function listing(Cooperative $coop, array $overrides = []): CropAvailability
    {
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
            'actual_sacks' => 78, 'actual_weight_kg' => 4500, 'recorded_by' => $driver->id,
            'status' => ReceivingRecord::STATUS_CONFIRMED, 'buying_price_per_kg' => 18, 'total_amount' => 81000,
        ]);

        return CropAvailability::create(array_merge([
            'cooperative_id' => $coop->id,
            'receiving_record_id' => $record->id,
            'crop_id' => $crop->id,
            'crop_grade_id' => $grade->id,
            'quantity_kg' => 4500,
            'selling_price_per_kg' => 25,
            'status' => CropAvailability::STATUS_AVAILABLE,
        ], $overrides));
    }

    private function approvedBuyer(): User
    {
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value, 'status' => 'active']);
        BuyerProfile::factory()->approved()->for($buyer)->create();

        return $buyer;
    }

    private function pendingBuyer(): User
    {
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value, 'status' => 'pending']);
        BuyerProfile::factory()->for($buyer)->create();

        return $buyer;
    }

    public function test_approved_buyer_can_place_order(): void
    {
        $coop = $this->cooperative();
        $listing = $this->listing($coop);
        $buyer = $this->approvedBuyer();

        $response = $this->actingAs($buyer)->post(route('buyer.orders.store'), [
            'crop_availability_id' => $listing->id,
            'quantity_kg' => 2000,
            'preferred_delivery_date' => now()->addDays(3)->toDateString(),
            'delivery_address' => 'Buyer warehouse, Davao City',
            'delivery_latitude' => 7.0731,
            'delivery_longitude' => 125.6128,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('buyer_orders', [
            'buyer_id' => $buyer->id,
            'cooperative_id' => $coop->id,
            'status' => 'submitted',
            'total_kg' => 2000,
        ]);
        $this->assertDatabaseHas('buyer_order_items', [
            'crop_availability_id' => $listing->id,
            'quantity_kg' => 2000,
            'rate_per_kg' => 25,
        ]);
    }

    public function test_unapproved_buyer_cannot_place_order(): void
    {
        $coop = $this->cooperative();
        $listing = $this->listing($coop);
        $buyer = $this->pendingBuyer();

        $response = $this->actingAs($buyer)->post(route('buyer.orders.store'), [
            'crop_availability_id' => $listing->id,
            'quantity_kg' => 100,
            'delivery_address' => 'Somewhere',
        ]);

        $response->assertSessionHasErrors('buyer');
        $this->assertDatabaseCount('buyer_orders', 0);
    }

    public function test_order_blocked_when_quantity_exceeds_remaining(): void
    {
        $coop = $this->cooperative();
        $listing = $this->listing($coop, ['quantity_kg' => 1000]);
        $buyer = $this->approvedBuyer();

        $response = $this->actingAs($buyer)->post(route('buyer.orders.store'), [
            'crop_availability_id' => $listing->id,
            'quantity_kg' => 5000,
            'delivery_address' => 'Somewhere',
            'delivery_latitude' => 7.0731,
            'delivery_longitude' => 125.6128,
        ]);

        $response->assertSessionHasErrors('quantity_kg');
        $this->assertDatabaseCount('buyer_orders', 0);
    }

    public function test_order_blocked_when_listing_has_no_price(): void
    {
        $coop = $this->cooperative();
        $listing = $this->listing($coop, ['selling_price_per_kg' => null]);
        $buyer = $this->approvedBuyer();

        $response = $this->actingAs($buyer)->post(route('buyer.orders.store'), [
            'crop_availability_id' => $listing->id,
            'quantity_kg' => 100,
            'delivery_address' => 'Somewhere',
            'delivery_latitude' => 7.0731,
            'delivery_longitude' => 125.6128,
        ]);

        $response->assertSessionHasErrors('crop_availability_id');
    }

    public function test_buyer_cannot_view_another_buyers_order(): void
    {
        $coop = $this->cooperative();
        $listing = $this->listing($coop);
        $buyer = $this->approvedBuyer();
        $otherBuyer = $this->approvedBuyer();

        $this->actingAs($buyer)->post(route('buyer.orders.store'), [
            'crop_availability_id' => $listing->id,
            'quantity_kg' => 100,
            'delivery_address' => 'Somewhere',
            'delivery_latitude' => 7.0731,
            'delivery_longitude' => 125.6128,
        ]);
        $order = \App\Models\BuyerOrder::first();

        $response = $this->actingAs($otherBuyer)->get(route('buyer.orders.show', $order));

        $response->assertForbidden();
    }
}
