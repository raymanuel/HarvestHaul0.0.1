<?php

namespace Tests\Feature;

use App\Models\CustomerCard;
use App\Models\OutboundOrder;
use App\Models\OutboundOrderLine;
use App\Models\PoolingJob;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutboundTrackTest extends TestCase
{
    use RefreshDatabase;

    private function coop(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name'       => 'GenSan AgCoop',
            'business_permit_no' => 'BL-999',
            'phone'              => '09171234567',
            'is_verified'        => true,
            'logistics_type'     => 'cooperative',
        ]);
        return $user;
    }

    private function dispatchedOrder(User $coop): OutboundOrder
    {
        $card = CustomerCard::factory()->create([
            'logistics_profile_id' => $coop->logisticsProfile->id,
            'name'                 => 'Robinsons GenSan',
            'latitude'             => 6.10,
            'longitude'            => 125.20,
        ]);
        $order = OutboundOrder::factory()->create([
            'customer_card_id'     => $card->id,
            'logistics_profile_id' => $coop->logisticsProfile->id,
            'status'               => 'confirmed',
            'tracking_token'       => 'tok1234567890abcdef',
            'total_kg'             => 150,
            'total_amount'         => 3500,
        ]);
        OutboundOrderLine::factory()->create([
            'outbound_order_id' => $order->id,
            'crop_type'         => 'Banana',
            'quantity_kg'       => 150,
            'rate_per_kg'       => 20,
            'subtotal'          => 3000,
        ]);
        $driver = User::factory()->driver()->create(['email_verified_at' => now()]);
        $truck = Truck::create([
            'logistics_profile_id' => $coop->logisticsProfile->id,
            'truck_name'           => 'T1',
            'plate_number'         => 'AAA-111',
            'capacity_kg'          => 2000,
            'status'               => 'available',
        ]);
        PoolingJob::create([
            'logistics_profile_id' => $coop->logisticsProfile->id,
            'truck_id'             => $truck->id,
            'driver_id'            => $driver->id,
            'status'               => 'confirmed',
            'leg_type'             => 'outbound',
            'customer_card_id'     => $card->id,
            'outbound_order_id'    => $order->id,
            'total_kg'             => 150,
            'truck_capacity_kg'    => 2000,
            'start_latitude'       => 6.05,
            'start_longitude'      => 125.13,
            'end_latitude'         => 6.10,
            'end_longitude'        => 125.20,
        ]);
        return $order;
    }

    public function test_customer_can_open_tracking_page_with_valid_token(): void
    {
        $order = $this->dispatchedOrder($this->coop());

        $response = $this->get(route('outbound.track', $order->tracking_token));
        $response->assertOk();
        $response->assertSee('Robinsons GenSan');
        $response->assertSee('Banana');
    }

    public function test_bad_token_is_404(): void
    {
        $this->get(route('outbound.track', 'does-not-exist'))->assertNotFound();
    }

    public function test_customer_confirms_receipt_completes_order_and_job(): void
    {
        $coop = $this->coop();
        $order = $this->dispatchedOrder($coop);
        $job = $order->poolingJob;
        $order->update(['status' => 'awaiting_confirmation']);
        $job->update(['status' => 'awaiting_confirmation']);

        $response = $this->post(route('outbound.track.confirm', $order->tracking_token));
        $response->assertRedirect(route('outbound.track.complete'));
        $response->assertSessionHas('success', "Delivery confirmed. Thank you for your business! Order #{$order->id} is now complete.");

        $order->refresh();
        $job->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNull($order->tracking_token);
        $this->assertSame('completed', $job->status->value);
        $this->assertNotNull($order->completed_at);
        $this->assertNotNull($order->confirmed_at);
        $this->assertNotNull($job->completed_at);
    }

    public function test_confirm_rejected_when_not_awaiting_confirmation(): void
    {
        $coop = $this->coop();
        $order = $this->dispatchedOrder($coop);

        $this->post(route('outbound.track.confirm', $order->tracking_token))
            ->assertSessionHasErrors('order');
    }

    public function test_confirm_is_idempotent_via_status_guard(): void
    {
        $coop = $this->coop();
        $order = $this->dispatchedOrder($coop);
        $order->update(['status' => 'awaiting_confirmation']);
        $order->poolingJob->update(['status' => 'awaiting_confirmation']);

        $this->post(route('outbound.track.confirm', $order->tracking_token))->assertRedirect(route('outbound.track.complete'));
        $this->post(route('outbound.track.confirm', $order->tracking_token))
            ->assertSessionHasErrors('order');
    }

    public function test_confirmation_page_renders(): void
    {
        $this->get(route('outbound.track.complete'))
            ->assertOk()
            ->assertSee('Thank you, delivery confirmed');
    }
}