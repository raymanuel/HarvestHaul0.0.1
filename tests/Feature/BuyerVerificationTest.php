<?php

namespace Tests\Feature;

use App\Models\BuyerProfile;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'email_verified_at' => now()]);
    }

    private function pendingBuyer(): User
    {
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value, 'status' => 'pending']);
        BuyerProfile::factory()->for($buyer)->create();

        return $buyer;
    }

    public function test_approve_moves_pending_to_approved(): void
    {
        $admin = $this->superAdmin();
        $buyer = $this->pendingBuyer();

        $response = $this->actingAs($admin)->post(route('admin.buyers.approve', $buyer));

        $response->assertRedirect();
        $this->assertEquals(BuyerProfile::STATUS_APPROVED, $buyer->buyerProfile->fresh()->status);
        $this->assertTrue($buyer->buyerProfile->fresh()->is_verified);
    }

    public function test_reject_requires_reason(): void
    {
        $admin = $this->superAdmin();
        $buyer = $this->pendingBuyer();

        $blocked = $this->actingAs($admin)->post(route('admin.buyers.reject', $buyer));
        $blocked->assertSessionHasErrors('rejection_reason');

        $response = $this->actingAs($admin)->post(route('admin.buyers.reject', $buyer), [
            'rejection_reason' => 'Business documents did not match.',
        ]);
        $response->assertRedirect();
        $this->assertEquals(BuyerProfile::STATUS_REJECTED, $buyer->buyerProfile->fresh()->status);
    }

    public function test_suspend_and_reactivate(): void
    {
        $admin = $this->superAdmin();
        $buyer = $this->pendingBuyer();
        $this->actingAs($admin)->post(route('admin.buyers.approve', $buyer));

        $suspend = $this->actingAs($admin)->post(route('admin.buyers.suspend', $buyer));
        $suspend->assertRedirect();
        $this->assertEquals(BuyerProfile::STATUS_SUSPENDED, $buyer->buyerProfile->fresh()->status);

        $reactivate = $this->actingAs($admin)->post(route('admin.buyers.reactivate', $buyer));
        $reactivate->assertRedirect();
        $this->assertEquals(BuyerProfile::STATUS_APPROVED, $buyer->buyerProfile->fresh()->status);
    }

    public function test_cannot_suspend_a_pending_buyer(): void
    {
        $admin = $this->superAdmin();
        $buyer = $this->pendingBuyer();

        $response = $this->actingAs($admin)->post(route('admin.buyers.suspend', $buyer));

        $response->assertSessionHasErrors('buyer');
        $this->assertEquals(BuyerProfile::STATUS_PENDING, $buyer->buyerProfile->fresh()->status);
    }

    public function test_cannot_reactivate_a_non_suspended_buyer(): void
    {
        $admin = $this->superAdmin();
        $buyer = $this->pendingBuyer();

        $response = $this->actingAs($admin)->post(route('admin.buyers.reactivate', $buyer));

        $response->assertSessionHasErrors('buyer');
    }
}
