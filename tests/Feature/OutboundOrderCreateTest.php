<?php

namespace Tests\Feature;

use App\Models\CustomerCard;
use App\Models\Negotiation;
use App\Models\NegotiationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutboundOrderCreateTest extends TestCase
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

    public function test_coop_creates_order_with_lines_and_totals(): void
    {
        $coop = $this->coop();
        $card = CustomerCard::factory()->create(['logistics_profile_id' => $coop->logisticsProfile->id]);

        $response = $this->actingAs($coop)->post(route('coop.outbound.store'), [
            'customer_card_id' => $card->id,
            'notes'            => 'Deliver to receiving dock',
            'lines'            => [
                ['crop_type' => 'Banana', 'quantity_kg' => 100, 'rate_per_kg' => 20],
                ['crop_type' => 'Coconut', 'quantity_kg' => 50, 'rate_per_kg' => 30],
            ],
        ]);

        $response->assertRedirect();
        $order = \App\Models\OutboundOrder::where('customer_card_id', $card->id)->first();
        $this->assertNotNull($order);
        $this->assertSame(150.0, (float) $order->total_kg);
        $this->assertSame(3500.0, (float) $order->total_amount);
        $this->assertCount(2, $order->orderLines);
        $this->assertSame(2000.0, (float) $order->orderLines->first()->subtotal);
    }

    public function test_validation_rejects_bad_lines(): void
    {
        $coop = $this->coop();
        $card = CustomerCard::factory()->create(['logistics_profile_id' => $coop->logisticsProfile->id]);

        $this->actingAs($coop)->post(route('coop.outbound.store'), [
            'customer_card_id' => $card->id,
            'lines'            => [
                ['crop_type' => '', 'quantity_kg' => 0, 'rate_per_kg' => -1],
            ],
        ])->assertSessionHasErrors(['lines.0.crop_type', 'lines.0.quantity_kg', 'lines.0.rate_per_kg']);
    }

    public function test_coop_suggestions_include_completed_deals(): void
    {
        $coop = $this->coop();
        $farmer = User::factory()->farmer()->create(['email_verified_at' => now()]);

        $harvest = \App\Models\Harvest::factory()->create([
            'user_id'           => $farmer->id,
            'crop_type'         => 'Banana',
            'quantity_kg'       => 500,
            'remaining_quantity_kg' => 500,
            'status'            => 'sold',
        ]);

        Negotiation::create([
            'buyer_id'          => $coop->id,
            'farmer_id'         => $farmer->id,
            'harvest_id'        => $harvest->id,
            'negotiated_price'  => 20,
            'negotiated_volume' => 500,
            'status'            => NegotiationStatus::COMPLETED->value,
        ]);

        $response = $this->actingAs($coop)->get(route('coop.outbound.create'));
        $response->assertOk();
        $response->assertSee('Banana');
    }
}