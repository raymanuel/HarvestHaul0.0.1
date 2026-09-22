<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BuyerOrder;
use App\Models\BuyerProfile;
use App\Models\Cooperative;
use App\Models\Crop;
use App\Models\CropAvailability;
use App\Models\CropCategory;
use App\Models\CropGrade;
use App\Models\FarmerPayment;
use App\Models\FarmerProfile;
use App\Models\HaulJob;
use App\Models\HaulRequest;
use App\Models\Notification;
use App\Models\ReceivingRecord;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Module 27 — a single continuous business flow spanning Modules 1-16/19/21/22:
 * cooperative approval -> farmer membership -> pickup request -> pickup trip
 * planning/routing -> GPS tracking -> trip execution -> receiving ->
 * procurement pricing/confirmation -> farmer payout -> crop availability ->
 * buyer approval -> buyer order -> order acceptance -> outbound delivery
 * planning -> delivery execution -> reporting, with audit log + notification
 * checks threaded throughout. Every step goes through the real HTTP route,
 * not direct model manipulation, except pure reference-data/fixture setup.
 */
class CooperativeLifecycleE2ETest extends TestCase
{
    use RefreshDatabase;

    public function test_full_cooperative_to_buyer_lifecycle(): void
    {
        Http::fake([
            'router.project-osrm.org/table/*' => Http::response(['code' => 'Ok', 'durations' => [[0, 300], [300, 0]], 'distances' => [[0, 5000], [5000, 0]]]),
            'router.project-osrm.org/route/*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 5000, 'duration' => 300, 'geometry' => ['coordinates' => [[125.17, 6.12]]]]]]),
        ]);

        // ── Fixture reference data (Module 12/20 — not the flow under test) ──
        $category = CropCategory::create(['name' => 'Grains', 'status' => 'active']);
        $crop = Crop::create(['crop_category_id' => $category->id, 'name' => 'Rice', 'status' => 'active']);
        $grade = CropGrade::create(['name' => 'Class A', 'is_active' => true, 'sort_order' => 1]);

        $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'email_verified_at' => now()]);

        // ── Module 1: cooperative registers, admin approves ──
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_PENDING,
            'latitude' => '6.1164', 'longitude' => '125.1716',
        ]);
        $coopAdmin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $coop->update(['coop_admin_user_id' => $coopAdmin->id]);

        $this->actingAs($superAdmin)->post(route('admin.cooperatives.approve', $coop))->assertRedirect();
        $this->assertEquals(Cooperative::STATUS_APPROVED, $coop->fresh()->status);

        // ── Module 3: farmer requests cooperative membership, coop approves ──
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        FarmerProfile::factory()->for($farmer)->create([
            'affiliation_type' => 'cooperative', 'cooperative_id' => $coop->id, 'membership_status' => 'pending',
        ]);

        $this->actingAs($coopAdmin)->post(route('coop.farmers.approve', $farmer))->assertRedirect();
        $this->assertEquals('approved', $farmer->farmerProfile->fresh()->membership_status);
        $this->assertDatabaseHas('notifications', ['user_id' => $farmer->id, 'category' => 'membership']);

        // ── Module 4: farmer submits a pickup request with coordinates ──
        $pickupDate = today()->addDay()->toDateString();
        $this->actingAs($farmer)->post(route('farmer.haul-requests.store'), [
            'crop_id' => $crop->id,
            'estimated_weight_kg' => 1000,
            'preferred_pickup_date' => $pickupDate,
            'pickup_window_start' => '08:00',
            'pickup_window_end' => '12:00',
            'pickup_location' => 'Barangay Fatima, GenSan',
            'pickup_location_lat' => 6.12,
            'pickup_location_lng' => 125.18,
        ])->assertRedirect();

        $haulRequest = HaulRequest::firstOrFail();
        $this->assertEquals(6.12, (float) $haulRequest->pickup_location_lat);

        // ── Module 5: coop approves the pickup request ──
        $this->actingAs($coopAdmin)->post(route('coop.haul-requests.approve', $haulRequest))->assertRedirect();
        $this->assertEquals(HaulRequest::STATUS_APPROVED, $haulRequest->fresh()->status);

        // ── Modules 6/7/9: coop consolidates + plans the pickup trip (routed via mocked OSRM) ──
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id, 'capacity_kg' => 5000, 'status' => 'available']);

        $this->actingAs($coopAdmin)->post(route('coop.pickups.store'), [
            'date' => $pickupDate,
            'requests' => [$haulRequest->id],
            'truck_id' => $truck->id,
            'delivery_personnel_id' => $driver->id,
        ])->assertRedirect();

        $job = HaulJob::firstOrFail();
        $this->assertEquals(HaulJob::STATUS_SCHEDULED, $job->status);
        $this->assertEquals(HaulRequest::STATUS_SCHEDULED, $haulRequest->fresh()->status);
        $stop = $job->stops()->firstOrFail();

        // ── Module 16: driver pings GPS while the trip is active, coop sees it live ──
        $this->actingAs($driver)->postJson(route('delivery.trips.location', $job), [
            'latitude' => 6.13, 'longitude' => 125.17,
        ])->assertOk();

        $this->actingAs($coopAdmin)->getJson(route('coop.tracking.location', $job))
            ->assertOk()->assertJson(['has_position' => true]);

        // ── Module 8: driver executes the trip (single stop -> trip auto-completes) ──
        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$stop, 'picked_up']))->assertRedirect();

        $this->assertEquals(HaulJob::STATUS_COMPLETED, $job->fresh()->status);
        $this->assertEquals('available', $truck->fresh()->status);
        $this->assertEquals(HaulRequest::STATUS_COMPLETED, $haulRequest->fresh()->status);

        // ── Module 10: field personnel records receiving ──
        $field = User::factory()->create(['role' => UserRole::FIELD_RECEIVING->value, 'cooperative_id' => $coop->id]);
        $this->actingAs($field)->post(route('field.receiving.store', [$job, $stop]), [
            'crop_grade_id' => $grade->id,
            'actual_sacks' => 20,
            'actual_weight_kg' => 980,
        ])->assertRedirect();

        $receiving = ReceivingRecord::firstOrFail();
        $this->assertEquals(ReceivingRecord::STATUS_PENDING, $receiving->status);

        // ── Module 11: coop prices, then confirms procurement ──
        $this->actingAs($coopAdmin)->post(route('coop.procurement.price', $receiving), [
            'buying_price_per_kg' => 20,
        ])->assertRedirect();
        $this->assertEquals(ReceivingRecord::STATUS_PRICED, $receiving->fresh()->status);

        $this->actingAs($coopAdmin)->post(route('coop.procurement.confirm', $receiving))->assertRedirect();
        $receiving->refresh();
        $this->assertEquals(ReceivingRecord::STATUS_CONFIRMED, $receiving->status);
        $this->assertEquals(19600.0, (float) $receiving->total_amount);

        // Confirming seeds sellable inventory (Module 12/14).
        $availability = CropAvailability::firstOrFail();
        $this->assertEquals(980.0, (float) $availability->quantity_kg);
        $this->assertNull($availability->selling_price_per_kg);

        // ── Module 11: coop pays the farmer in full ──
        $this->actingAs($coopAdmin)->post(route('coop.procurement.payments.store', $receiving), [
            'amount' => 19600, 'method' => FarmerPayment::METHOD_CASH,
        ])->assertRedirect();
        $this->assertEquals('paid', $receiving->fresh()->paymentStatus());
        $this->assertDatabaseHas('notifications', ['user_id' => $farmer->id, 'category' => 'procurement']);

        // ── Module 14: coop sets a selling price on the new inventory ──
        $this->actingAs($coopAdmin)->post(route('coop.availability.price', $availability), [
            'selling_price_per_kg' => 30,
        ])->assertRedirect();
        $this->assertEquals(30.0, (float) $availability->fresh()->selling_price_per_kg);

        // ── Module 13: buyer registers, admin approves ──
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        BuyerProfile::factory()->for($buyer)->create(['status' => BuyerProfile::STATUS_PENDING]);

        $this->actingAs($superAdmin)->post(route('admin.buyers.approve', $buyer))->assertRedirect();
        $this->assertEquals(BuyerProfile::STATUS_APPROVED, $buyer->buyerProfile->fresh()->status);
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'category' => 'account']);

        // ── Module 13: buyer places an order against the new listing ──
        $this->actingAs($buyer)->post(route('buyer.orders.store'), [
            'crop_availability_id' => $availability->id,
            'quantity_kg' => 500,
            'delivery_address' => 'Buyer warehouse, GenSan',
            'delivery_latitude' => 6.05, 'delivery_longitude' => 125.20,
        ])->assertRedirect();

        $order = BuyerOrder::firstOrFail();
        $this->assertEquals(BuyerOrder::STATUS_SUBMITTED, $order->status);
        $this->assertEquals(15000.0, (float) $order->total_amount);

        // ── Module 13: coop accepts the order (reserves stock) ──
        $this->actingAs($coopAdmin)->post(route('coop.buyer-orders.accept', $order))->assertRedirect();
        $this->assertEquals(BuyerOrder::STATUS_ACCEPTED, $order->fresh()->status);
        $this->assertEquals(500.0, (float) $availability->fresh()->sold_kg);

        // ── Module 14: coop plans + creates the outbound delivery trip ──
        $deliveryDate = today()->addDays(2)->toDateString();
        $this->actingAs($coopAdmin)->post(route('coop.outbound.store'), [
            'date' => $deliveryDate,
            'orders' => [$order->id],
            'truck_id' => $truck->id,
            'delivery_personnel_id' => $driver->id,
        ])->assertRedirect();

        $deliveryJob = HaulJob::where('job_type', HaulJob::JOB_TYPE_DELIVERY)->firstOrFail();
        $this->assertEquals(BuyerOrder::STATUS_READY_FOR_DELIVERY, $order->fresh()->status);
        $deliveryStop = $deliveryJob->stops()->firstOrFail();

        // ── Module 8: driver delivers (single stop -> trip auto-completes) ──
        $this->actingAs($driver)->post(route('delivery.trips.stop-status', [$deliveryStop, 'delivered']))->assertRedirect();

        $this->assertEquals(BuyerOrder::STATUS_DELIVERED, $order->fresh()->status);
        $this->assertEquals(HaulJob::STATUS_COMPLETED, $deliveryJob->fresh()->status);
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'title' => 'Order delivered']);

        // ── Module 19: reports reflect the completed lifecycle ──
        $from = today()->toDateString();
        $to = today()->addDays(3)->toDateString();

        $this->actingAs($coopAdmin)->get(route('coop.reports.procurement', compact('from', 'to')))
            ->assertOk()->assertViewHas('totalSpend', fn ($v) => (float) $v === 19600.0);

        $this->actingAs($coopAdmin)->get(route('coop.reports.sales', compact('from', 'to')))
            ->assertOk()->assertViewHas('totalRevenue', fn ($v) => (float) $v === 15000.0);

        $this->actingAs($coopAdmin)->get(route('coop.reports.payouts', compact('from', 'to')))
            ->assertOk()->assertViewHas('totalPaid', fn ($v) => (float) $v === 19600.0);

        // ── Module 21: the whole flow left a real audit trail ──
        foreach ([
            'approved', 'approve_farmer_membership', 'approve_haul_request', 'create_pickup_trip',
            'update_stop_status', 'price_receiving', 'confirm_receiving', 'record_farmer_payment',
            'approved', 'accept_buyer_order', 'create_delivery_trip', 'complete_haul_job',
        ] as $action) {
            $this->assertTrue(
                AuditLog::where('action', $action)->exists(),
                "Expected an audit log entry for action \"{$action}\"."
            );
        }

        // ── Module 22: notifications reached every real actor across the flow ──
        $this->assertTrue(Notification::where('user_id', $farmer->id)->count() >= 3);
        $this->assertTrue(Notification::where('user_id', $buyer->id)->count() >= 2);
        $this->assertTrue(Notification::where('user_id', $driver->id)->count() >= 1);
    }
}
