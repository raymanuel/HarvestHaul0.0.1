<?php

namespace Tests\Feature;

use App\Models\BuyerOrder;
use App\Models\Cooperative;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerPaymentTest extends TestCase
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

    private function acceptedOrder(Cooperative $coop): array
    {
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        $order = BuyerOrder::create([
            'buyer_id' => $buyer->id, 'cooperative_id' => $coop->id,
            'reference' => 'ORD-TEST', 'status' => BuyerOrder::STATUS_ACCEPTED,
            'total_kg' => 100, 'total_amount' => 2500,
            'delivery_address' => 'Somewhere',
        ]);

        return compact('admin', 'buyer', 'order');
    }

    public function test_payment_moves_pending_to_partial_to_paid(): void
    {
        $coop = $this->coop();
        ['admin' => $admin, 'order' => $order] = $this->acceptedOrder($coop);

        $this->assertEquals('pending', $order->paymentStatus());

        $this->actingAs($admin)->post(route('coop.buyer-orders.payments.store', $order), [
            'amount' => 1000, 'method' => 'cash',
        ])->assertRedirect();
        $this->assertEquals('partial', $order->fresh()->paymentStatus());
        $this->assertEquals(1500.0, $order->fresh()->balanceDue());

        $this->actingAs($admin)->post(route('coop.buyer-orders.payments.store', $order), [
            'amount' => 1500, 'method' => 'bank_transfer', 'reference' => 'BT-1',
        ])->assertRedirect();
        $this->assertEquals('paid', $order->fresh()->paymentStatus());
        $this->assertEquals(0.0, $order->fresh()->balanceDue());
    }

    public function test_overpayment_blocked(): void
    {
        $coop = $this->coop();
        ['admin' => $admin, 'order' => $order] = $this->acceptedOrder($coop);

        $response = $this->actingAs($admin)->post(route('coop.buyer-orders.payments.store', $order), [
            'amount' => 3000, 'method' => 'cash',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('buyer_payments', 0);
    }

    public function test_payment_blocked_before_acceptance(): void
    {
        $coop = $this->coop();
        ['admin' => $admin, 'order' => $order] = $this->acceptedOrder($coop);
        $order->update(['status' => BuyerOrder::STATUS_SUBMITTED]);

        $response = $this->actingAs($admin)->post(route('coop.buyer-orders.payments.store', $order), [
            'amount' => 100, 'method' => 'cash',
        ]);

        $response->assertSessionHasErrors('order');
        $this->assertDatabaseCount('buyer_payments', 0);
    }

    public function test_payment_blocked_after_rejection(): void
    {
        $coop = $this->coop();
        ['admin' => $admin, 'order' => $order] = $this->acceptedOrder($coop);
        $order->update(['status' => BuyerOrder::STATUS_REJECTED]);

        $response = $this->actingAs($admin)->post(route('coop.buyer-orders.payments.store', $order), [
            'amount' => 100, 'method' => 'cash',
        ]);

        $response->assertSessionHasErrors('order');
    }

    public function test_fully_paid_order_blocks_further_payment(): void
    {
        $coop = $this->coop();
        ['admin' => $admin, 'order' => $order] = $this->acceptedOrder($coop);

        $this->actingAs($admin)->post(route('coop.buyer-orders.payments.store', $order), [
            'amount' => 2500, 'method' => 'cash',
        ])->assertRedirect();

        $response = $this->actingAs($admin)->post(route('coop.buyer-orders.payments.store', $order), [
            'amount' => 1, 'method' => 'cash',
        ]);

        $response->assertSessionHasErrors('order');
    }

    public function test_other_cooperative_cannot_record_payment(): void
    {
        $coop = $this->coop();
        ['order' => $order] = $this->acceptedOrder($coop);
        $otherAdmin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $this->coop('other@example.com')->id]);

        $response = $this->actingAs($otherAdmin)->post(route('coop.buyer-orders.payments.store', $order), [
            'amount' => 100, 'method' => 'cash',
        ]);

        $response->assertForbidden();
    }

    public function test_payment_writes_audit_log_and_notifies_buyer(): void
    {
        $coop = $this->coop();
        ['admin' => $admin, 'buyer' => $buyer, 'order' => $order] = $this->acceptedOrder($coop);

        $this->actingAs($admin)->post(route('coop.buyer-orders.payments.store', $order), [
            'amount' => 1000, 'method' => 'cash',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['action' => 'record_buyer_payment', 'target_type' => 'buyer_order', 'target_id' => $order->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'title' => 'Payment received', 'category' => 'buyer_order']);
    }
}
