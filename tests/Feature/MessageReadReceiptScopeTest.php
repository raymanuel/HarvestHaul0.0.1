<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\Message;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageReadReceiptScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * validPartners()/threadCooperativeId() mean a (sender_id, recipient_id)
     * pair is always pinned to one cooperative in every reachable app flow
     * today, so this exact mismatch can't currently be produced by clicking
     * through the UI. Scoping the read-receipt query by cooperative_id
     * anyway — matching the $messages query right above it in show() — is
     * defense-in-depth: it costs nothing and stops this from becoming a real
     * leak if that invariant ever changes (e.g. a coop admin transferred to
     * a different cooperative). This test documents that guarantee directly.
     */
    public function test_opening_a_thread_never_marks_a_different_cooperatives_messages_as_read(): void
    {
        $coopA = Cooperative::factory()->create();
        $coopB = Cooperative::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coopA->id]);
        $other = User::factory()->create();

        $inThisThread = Message::factory()->create([
            'sender_id' => $other->id,
            'recipient_id' => $admin->id,
            'cooperative_id' => $coopA->id,
            'read_at' => null,
        ]);

        $inADifferentCooperativesThread = Message::factory()->create([
            'sender_id' => $other->id,
            'recipient_id' => $admin->id,
            'cooperative_id' => $coopB->id,
            'read_at' => null,
        ]);

        // assertCoopScoped() requires $other to be a valid partner; make the
        // pairing legitimate for $admin so the request itself isn't blocked.
        $other->update(['role' => UserRole::FARMER->value, 'cooperative_id' => $coopA->id]);

        $this->actingAs($admin)->get(route('messages.show', $other))->assertOk();

        $this->assertNotNull($inThisThread->fresh()->read_at);
        $this->assertNull($inADifferentCooperativesThread->fresh()->read_at);
    }

    public function test_unread_count_in_poll_is_also_scoped_to_the_threads_cooperative(): void
    {
        $coopA = Cooperative::factory()->create();
        $coopB = Cooperative::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coopA->id]);
        $other = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coopA->id]);

        Message::factory()->create([
            'sender_id' => $other->id, 'recipient_id' => $admin->id,
            'cooperative_id' => $coopA->id, 'read_at' => null,
        ]);
        Message::factory()->create([
            'sender_id' => $other->id, 'recipient_id' => $admin->id,
            'cooperative_id' => $coopB->id, 'read_at' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('messages.poll', $other));

        $response->assertOk();
        $response->assertJson(['unread' => 1]);
    }
}
