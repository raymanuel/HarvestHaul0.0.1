<?php

namespace Tests\Feature;

use App\Models\BuyerOrder;
use App\Models\Cooperative;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BuyerConfirmReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function order(Cooperative $coop, User $buyer, string $status): BuyerOrder
    {
        return BuyerOrder::create([
            'buyer_id' => $buyer->id, 'cooperative_id' => $coop->id,
            'reference' => 'ORD-'.Str::random(6), 'status' => $status,
            'total_kg' => 100, 'total_amount' => 5000,
        ]);
    }

    public function test_buyer_can_confirm_receipt_of_a_delivered_order(): void
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        $order = $this->order($coop, $buyer, BuyerOrder::STATUS_DELIVERED);

        $response = $this->actingAs($buyer)->post(route('buyer.orders.confirm-receipt', $order));

        $response->assertRedirect();
        $order->refresh();
        $this->assertEquals(BuyerOrder::STATUS_COMPLETED, $order->status);
        $this->assertNotNull($order->confirmed_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'category' => 'buyer_order']);
    }

    public function test_confirm_receipt_is_blocked_before_delivered(): void
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        $order = $this->order($coop, $buyer, BuyerOrder::STATUS_OUT_FOR_DELIVERY);

        $response = $this->actingAs($buyer)->post(route('buyer.orders.confirm-receipt', $order));

        $response->assertSessionHasErrors('order');
        $this->assertEquals(BuyerOrder::STATUS_OUT_FOR_DELIVERY, $order->refresh()->status);
    }

    public function test_confirm_receipt_cannot_be_submitted_twice(): void
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        $order = $this->order($coop, $buyer, BuyerOrder::STATUS_COMPLETED);

        $response = $this->actingAs($buyer)->post(route('buyer.orders.confirm-receipt', $order));

        $response->assertSessionHasErrors('order');
    }

    public function test_a_different_buyer_cannot_confirm_receipt_of_someone_elses_order(): void
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        $otherBuyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        $order = $this->order($coop, $buyer, BuyerOrder::STATUS_DELIVERED);

        $response = $this->actingAs($otherBuyer)->post(route('buyer.orders.confirm-receipt', $order));

        $response->assertForbidden();
    }
}
