<?php

namespace Tests\Feature;

use App\Models\BuyerOrder;
use App\Models\Cooperative;
use App\Models\Message;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingTest extends TestCase
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
        return User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
    }

    public function test_farmer_can_start_new_thread_with_coop_admin(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);

        $this->actingAs($farmer)->get(route('messages.show', $admin))->assertOk();

        $response = $this->actingAs($farmer)->post(route('messages.store', $admin), ['body' => 'Hello coop!']);

        $response->assertRedirect();
        $this->assertDatabaseHas('messages', ['sender_id' => $farmer->id, 'recipient_id' => $admin->id, 'cooperative_id' => $coop->id, 'body' => 'Hello coop!']);
    }

    public function test_delivery_personnel_cannot_message_farmer(): void
    {
        $coop = $this->cooperative();
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);

        $response = $this->actingAs($driver)->post(route('messages.store', $farmer), ['body' => 'Hey']);

        $response->assertForbidden();
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_buyer_can_message_coop_admin_of_cooperative_they_ordered_from(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        BuyerOrder::create([
            'buyer_id' => $buyer->id, 'cooperative_id' => $coop->id, 'reference' => 'ORD-1',
            'status' => BuyerOrder::STATUS_ACCEPTED, 'total_kg' => 100, 'total_amount' => 2500,
            'delivery_address' => 'Somewhere',
        ]);

        $this->actingAs($buyer)->get(route('messages.show', $admin))->assertOk();

        $response = $this->actingAs($buyer)->post(route('messages.store', $admin), [
            'body' => 'When will my order ship?',
            'context_type' => 'buyer_order',
            'context_id' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('messages', [
            'sender_id' => $buyer->id, 'recipient_id' => $admin->id, 'cooperative_id' => $coop->id,
            'context_type' => 'buyer_order', 'context_id' => 1,
        ]);
    }

    public function test_buyer_cannot_message_coop_admin_of_cooperative_never_ordered_from(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);

        $response = $this->actingAs($buyer)->post(route('messages.store', $admin), ['body' => 'Hi']);

        $response->assertForbidden();
    }

    public function test_coop_admin_can_message_buyer_who_ordered_from_them(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        BuyerOrder::create([
            'buyer_id' => $buyer->id, 'cooperative_id' => $coop->id, 'reference' => 'ORD-1',
            'status' => BuyerOrder::STATUS_ACCEPTED, 'total_kg' => 100, 'total_amount' => 2500,
            'delivery_address' => 'Somewhere',
        ]);

        $response = $this->actingAs($admin)->post(route('messages.store', $buyer), ['body' => 'Confirming your order.']);

        $response->assertRedirect();
        $this->assertDatabaseHas('messages', ['sender_id' => $admin->id, 'recipient_id' => $buyer->id]);
    }

    public function test_context_only_persists_on_opening_message(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);

        $this->actingAs($farmer)->post(route('messages.store', $admin), [
            'body' => 'first', 'context_type' => 'haul_request', 'context_id' => 5,
        ]);
        $this->actingAs($farmer)->post(route('messages.store', $admin), [
            'body' => 'second', 'context_type' => 'haul_request', 'context_id' => 999,
        ]);

        $this->assertDatabaseHas('messages', ['body' => 'first', 'context_type' => 'haul_request', 'context_id' => 5]);
        $this->assertDatabaseHas('messages', ['body' => 'second', 'context_type' => null, 'context_id' => null]);
    }

    public function test_farmer_cannot_message_admin_of_another_cooperative(): void
    {
        $coop = $this->cooperative('GenSan AgCoop');
        $otherCoop = $this->cooperative('Davao AgCoop');
        $otherAdmin = $this->coopAdmin($otherCoop);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);

        $response = $this->actingAs($farmer)->post(route('messages.store', $otherAdmin), ['body' => 'Hi']);

        $response->assertForbidden();
    }

    public function test_cannot_message_self(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);

        $response = $this->actingAs($admin)->post(route('messages.store', $admin), ['body' => 'Hi']);

        $response->assertForbidden();
    }

    public function test_index_lists_partners_with_and_without_existing_threads(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);

        $this->actingAs($admin)->get(route('messages.index'))->assertOk();
        $this->actingAs($farmer)->get(route('messages.index'))->assertOk();
    }

    public function test_marking_thread_read_clears_unread_count(): void
    {
        $coop = $this->cooperative();
        $admin = $this->coopAdmin($coop);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);

        Message::create(['sender_id' => $farmer->id, 'recipient_id' => $admin->id, 'cooperative_id' => $coop->id, 'body' => 'hi']);

        $this->actingAs($admin)->get(route('messages.show', $farmer))->assertOk();

        $this->assertDatabaseHas('messages', ['sender_id' => $farmer->id, 'recipient_id' => $admin->id]);
        $this->assertNotNull(Message::first()->fresh()->read_at);
    }
}
