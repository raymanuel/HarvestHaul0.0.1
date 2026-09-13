<?php

namespace Tests\Feature;

use App\Models\CustomerCard;
use App\Models\OutboundOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCardCrudTest extends TestCase
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

    private function independentLogistics(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name'       => 'Linis Trucking',
            'business_permit_no' => 'BL-111',
            'phone'              => '09171234568',
            'is_verified'        => true,
            'logistics_type'     => 'company',
        ]);
        return $user;
    }

    public function test_coop_can_view_and_create_customer(): void
    {
        $coop = $this->coop();

        $response = $this->actingAs($coop)->get(route('coop.customers.index'));
        $response->assertOk();

        $response = $this->actingAs($coop)->post(route('coop.customers.store'), [
            'name'          => "KCC Mart de GenSan",
            'business_type' => 'grocery',
            'contact'       => '09171234567',
            'address'       => 'Jose Catolico Sr. Ave',
            'latitude'      => 6.06,
            'longitude'     => 125.17,
        ]);

        $response->assertRedirect(route('coop.customers.index'));
        $this->assertDatabaseHas('customer_cards', [
            'name'                 => "KCC Mart de GenSan",
            'logistics_profile_id' => $coop->logisticsProfile->id,
        ]);
    }

    public function test_independent_logistics_cannot_access_customers(): void
    {
        $independent = $this->independentLogistics();
        $this->actingAs($independent)->get(route('coop.customers.index'))->assertForbidden();
    }

    public function test_coop_can_edit_and_delete_own_customer(): void
    {
        $coop = $this->coop();
        $card = CustomerCard::factory()->create(['logistics_profile_id' => $coop->logisticsProfile->id]);

        $this->actingAs($coop)->put(route('coop.customers.update', $card), [
            'name' => 'Updated Customer',
        ])->assertRedirect();

        $this->assertDatabaseHas('customer_cards', ['id' => $card->id, 'name' => 'Updated Customer']);

        $this->actingAs($coop)->delete(route('coop.customers.destroy', $card))->assertRedirect();
        $this->assertDatabaseMissing('customer_cards', ['id' => $card->id]);
    }

    public function test_coop_cannot_delete_customer_with_orders(): void
    {
        $coop = $this->coop();
        $card = CustomerCard::factory()->create(['logistics_profile_id' => $coop->logisticsProfile->id]);
        $order = OutboundOrder::factory()->create([
            'customer_card_id'     => $card->id,
            'logistics_profile_id' => $coop->logisticsProfile->id,
        ]);

        $this->actingAs($coop)->delete(route('coop.customers.destroy', $card))
            ->assertRedirect()
            ->assertSessionHas('error', "Customer {$card->name} has 1 order(s), so they can't be removed. Keep the card, or finish their open orders first.");

        $this->assertDatabaseHas('customer_cards', ['id' => $card->id]);
        $this->assertDatabaseHas('outbound_orders', ['id' => $order->id]);
        $this->assertSame(1, $card->outboundOrders()->count());
    }

    public function test_coop_cannot_edit_other_coops_customer(): void
    {
        $coop = $this->coop();
        $other = CustomerCard::factory()->create();

        $this->actingAs($coop)->put(route('coop.customers.update', $other), ['name' => 'Nope'])->assertNotFound();
    }
}