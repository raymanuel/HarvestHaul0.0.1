<?php

namespace Tests\Feature;

use App\Models\CustomerCard;
use App\Models\Harvest;
use App\Models\OutboundOrder;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoCompleteOutboundTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_completes_stale_outbound_orders(): void
    {
        $coop = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $coop->logisticsProfile()->create([
            'company_name' => 'GenSan AgCoop', 'business_permit_no' => 'BL-999',
            'phone' => '09171234567', 'is_verified' => true, 'logistics_type' => 'cooperative',
        ]);
        $card = CustomerCard::factory()->create(['logistics_profile_id' => $coop->logisticsProfile->id]);
        $order = OutboundOrder::factory()->create([
            'customer_card_id' => $card->id, 'logistics_profile_id' => $coop->logisticsProfile->id,
            'status' => 'awaiting_confirmation', 'tracking_token' => 'stale-token',
            'delivered_at' => now()->subHours(50), 'completed_at' => now()->subHours(50), 'confirmed_at' => null,
        ]);
        $truck = Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'logistics_profile_id' => $coop->logisticsProfile->id, 'truck_name' => 'T1',
            'plate_number' => 'AAA-111', 'capacity_kg' => 2000, 'status' => 'available',
        ]);
        PoolingJob::create([
            'logistics_profile_id' => $coop->logisticsProfile->id, 'truck_id' => $truck->id,
            'driver_id' => null, 'status' => 'awaiting_confirmation', 'leg_type' => 'outbound',
            'customer_card_id' => $card->id, 'outbound_order_id' => $order->id,
            'total_kg' => 150, 'truck_capacity_kg' => 2000, 'completed_at' => now()->subHours(50),
        ]);

        $this->artisan('deliveries:auto-complete')->assertSuccessful();

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->confirmed_at);
        $this->assertNull($order->tracking_token);

        $job = $order->poolingJob->fresh();
        $this->assertSame('completed', $job->status->value);
        $this->assertTrue($job->completed_at->greaterThan(now()->subMinutes(1)));

        $this->assertDatabaseHas('trucks', ['id' => $truck->id, 'status' => 'available']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auto_complete_outbound',
            'target_type' => 'outbound_orders',
            'target_id' => $order->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $coop->id,
            'link' => route('coop.outbound.show', $order),
        ]);
    }

    public function test_command_leaves_fresh_outbound_orders_awaiting(): void
    {
        $coop = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $coop->logisticsProfile()->create([
            'company_name' => 'Tanza AgCoop', 'business_permit_no' => 'BL-998',
            'phone' => '09171234567', 'is_verified' => true, 'logistics_type' => 'cooperative',
        ]);
        $card = CustomerCard::factory()->create(['logistics_profile_id' => $coop->logisticsProfile->id]);
        $order = OutboundOrder::factory()->create([
            'customer_card_id' => $card->id, 'logistics_profile_id' => $coop->logisticsProfile->id,
            'status' => 'awaiting_confirmation', 'tracking_token' => 'fresh-token',
            'delivered_at' => now(), 'completed_at' => now(), 'confirmed_at' => null,
        ]);
        $truck = Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'logistics_profile_id' => $coop->logisticsProfile->id, 'truck_name' => 'T2',
            'plate_number' => 'BBB-222', 'capacity_kg' => 2000, 'status' => 'available',
        ]);
        PoolingJob::create([
            'logistics_profile_id' => $coop->logisticsProfile->id, 'truck_id' => $truck->id,
            'driver_id' => null, 'status' => 'awaiting_confirmation', 'leg_type' => 'outbound',
            'customer_card_id' => $card->id, 'outbound_order_id' => $order->id,
            'total_kg' => 150, 'truck_capacity_kg' => 2000, 'completed_at' => now(),
        ]);

        $this->artisan('deliveries:auto-complete')->assertSuccessful();

        $this->assertSame('awaiting_confirmation', $order->fresh()->status);
        $this->assertSame(PoolingJobStatus::AWAITING_CONFIRMATION, $order->poolingJob->fresh()->status);
    }

    public function test_command_keeps_inbound_behavior_intact(): void
    {
        $coop = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $coop->logisticsProfile()->create([
            'company_name' => 'Buhangin AgCoop', 'business_permit_no' => 'BL-997',
            'phone' => '09171234567', 'is_verified' => true, 'logistics_type' => 'cooperative',
        ]);
        $truck = Truck::create([
            'cooperative_id' => \App\Models\Cooperative::factory(),
            'logistics_profile_id' => $coop->logisticsProfile->id, 'truck_name' => 'T3',
            'plate_number' => 'CCC-333', 'capacity_kg' => 2000, 'status' => 'available',
        ]);
        $job = PoolingJob::create([
            'logistics_profile_id' => $coop->logisticsProfile->id, 'truck_id' => $truck->id,
            'driver_id' => null, 'status' => 'awaiting_confirmation', 'leg_type' => 'inbound',
            'total_kg' => 150, 'truck_capacity_kg' => 2000, 'completed_at' => now()->subHours(50),
        ]);
        $harvest = Harvest::factory()->create(['quantity_kg' => 150]);
        $job->harvests()->attach($harvest->id, ['quantity_kg' => 150, 'pickup_order' => 1, 'status' => 'delivered']);

        $this->artisan('deliveries:auto-complete')->assertSuccessful();

        $this->assertSame(PoolingJobStatus::COMPLETED, $job->fresh()->status);

        $pivot = $job->fresh()->harvests()->first()->pivot;
        $this->assertNotNull($pivot->buyer_confirmed_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auto_complete_delivery',
            'target_type' => 'pooling_jobs',
            'target_id' => $job->id,
        ]);
    }
}