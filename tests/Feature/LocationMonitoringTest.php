<?php

namespace Tests\Feature;

use App\Models\BuyerOrder;
use App\Models\Cooperative;
use App\Models\Crop;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\Truck;
use App\Models\TrackingRecord;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function coop(string $email = 'coop@example.com'): Cooperative
    {
        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => $email,
            'status' => Cooperative::STATUS_APPROVED,
        ]);
    }

    private function deliveryScene(): array
    {
        $coop = $this->coop();
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'status' => 'in_use']);

        $order = BuyerOrder::create([
            'buyer_id' => $buyer->id, 'cooperative_id' => $coop->id,
            'reference' => 'ORD-TEST', 'status' => BuyerOrder::STATUS_READY_FOR_DELIVERY,
            'total_kg' => 1000, 'total_amount' => 25000,
            'delivery_address' => 'Test address', 'delivery_latitude' => 6.1, 'delivery_longitude' => 125.1,
        ]);

        $job = HaulJob::create([
            'cooperative_id' => $coop->id, 'job_type' => HaulJob::JOB_TYPE_DELIVERY,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $truck->id,
            'pickup_date' => today(), 'status' => HaulJob::STATUS_SCHEDULED,
        ]);
        HaulJobStop::create([
            'haul_job_id' => $job->id, 'buyer_order_id' => $order->id,
            'sequence_no' => 1, 'status' => HaulJobStop::STATUS_PENDING,
        ]);

        return compact('coop', 'driver', 'buyer', 'order', 'job');
    }

    public function test_driver_can_post_location_while_trip_active(): void
    {
        $ctx = $this->deliveryScene();

        $response = $this->actingAs($ctx['driver'])->postJson(route('delivery.trips.location', $ctx['job']), [
            'latitude' => 6.15, 'longitude' => 125.12, 'speed_kmh' => 40,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tracking_records', [
            'job_type' => HaulJob::class, 'job_id' => $ctx['job']->id, 'driver_id' => $ctx['driver']->id,
        ]);
    }

    public function test_driver_cannot_post_location_once_trip_completed(): void
    {
        $ctx = $this->deliveryScene();
        $ctx['job']->update(['status' => HaulJob::STATUS_COMPLETED]);

        $response = $this->actingAs($ctx['driver'])->post(route('delivery.trips.location', $ctx['job']), [
            'latitude' => 6.15, 'longitude' => 125.12,
        ]);

        $response->assertSessionHasErrors('location');
        $this->assertDatabaseCount('tracking_records', 0);
    }

    public function test_other_driver_cannot_post_location(): void
    {
        $ctx = $this->deliveryScene();
        $otherDriver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $ctx['coop']->id]);

        $response = $this->actingAs($otherDriver)->post(route('delivery.trips.location', $ctx['job']), [
            'latitude' => 6.15, 'longitude' => 125.12,
        ]);

        $response->assertForbidden();
    }

    public function test_coop_admin_sees_latest_position_for_own_cooperative_job(): void
    {
        $ctx = $this->deliveryScene();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $ctx['coop']->id]);
        $ctx['job']->tracking()->create(['driver_id' => $ctx['driver']->id, 'latitude' => 6.2, 'longitude' => 125.2, 'posted_at' => now()]);

        $response = $this->actingAs($admin)->getJson(route('coop.tracking.location', $ctx['job']));

        $response->assertOk()->assertJson(['has_position' => true, 'lat' => 6.2, 'lng' => 125.2]);
    }

    public function test_coop_admin_of_another_cooperative_is_blocked(): void
    {
        $ctx = $this->deliveryScene();
        $otherAdmin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $this->coop('other@example.com')->id]);

        $response = $this->actingAs($otherAdmin)->getJson(route('coop.tracking.location', $ctx['job']));

        $response->assertForbidden();
    }

    public function test_buyer_sees_latest_position_for_own_order(): void
    {
        $ctx = $this->deliveryScene();
        $ctx['job']->tracking()->create(['driver_id' => $ctx['driver']->id, 'latitude' => 6.3, 'longitude' => 125.3, 'posted_at' => now()]);

        $response = $this->actingAs($ctx['buyer'])->getJson(route('buyer.orders.track.location', $ctx['order']));

        $response->assertOk()->assertJson(['has_position' => true, 'lat' => 6.3, 'lng' => 125.3]);
    }

    public function test_other_buyer_is_blocked_from_order_location(): void
    {
        $ctx = $this->deliveryScene();
        $otherBuyer = User::factory()->create(['role' => UserRole::BUYER->value]);

        $response = $this->actingAs($otherBuyer)->getJson(route('buyer.orders.track.location', $ctx['order']));

        $response->assertForbidden();
    }

    public function test_buyer_sees_no_position_before_order_is_dispatched(): void
    {
        $coop = $this->coop();
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        $order = BuyerOrder::create([
            'buyer_id' => $buyer->id, 'cooperative_id' => $coop->id,
            'reference' => 'ORD-UNDISPATCHED', 'status' => BuyerOrder::STATUS_ACCEPTED,
            'total_kg' => 500, 'total_amount' => 12500, 'delivery_address' => 'Somewhere',
        ]);

        $response = $this->actingAs($buyer)->getJson(route('buyer.orders.track.location', $order));

        $response->assertOk()->assertJson(['has_position' => false]);
    }

    public function test_farmer_sees_latest_position_for_own_pickup(): void
    {
        $coop = $this->coop();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $crop = Crop::factory()->create();

        $haulRequest = HaulRequest::create([
            'farmer_id' => $farmer->id, 'cooperative_id' => $coop->id, 'crop_id' => $crop->id,
            'estimated_weight_kg' => 1000, 'status' => HaulRequest::STATUS_SCHEDULED,
        ]);
        $job = HaulJob::create([
            'haul_request_id' => $haulRequest->id, 'cooperative_id' => $coop->id, 'job_type' => HaulJob::JOB_TYPE_PICKUP,
            'delivery_personnel_id' => $driver->id, 'pickup_date' => today(), 'status' => HaulJob::STATUS_SCHEDULED,
        ]);
        $job->tracking()->create(['driver_id' => $driver->id, 'latitude' => 7.0, 'longitude' => 125.5, 'posted_at' => now()]);

        $response = $this->actingAs($farmer)->getJson(route('farmer.haul-requests.track.location', $haulRequest));

        $response->assertOk()->assertJson(['has_position' => true, 'lat' => 7.0, 'lng' => 125.5]);
    }

    public function test_other_farmer_is_blocked_from_pickup_location(): void
    {
        $coop = $this->coop();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $otherFarmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $crop = Crop::factory()->create();

        $haulRequest = HaulRequest::create([
            'farmer_id' => $farmer->id, 'cooperative_id' => $coop->id, 'crop_id' => $crop->id,
            'estimated_weight_kg' => 1000, 'status' => HaulRequest::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($otherFarmer)->getJson(route('farmer.haul-requests.track.location', $haulRequest));

        $response->assertForbidden();
    }
}
