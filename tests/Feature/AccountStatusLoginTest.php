<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountStatusLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 'suspended' and 'deactivated' previously couldn't even be stored: sqlite's
     * CHECK constraint rejected them because the enum-widening migration only
     * ran on mysql. Proves the migration is now portable, and that the login
     * gate (LoginController) actually blocks both statuses, not just 'inactive'.
     */
    public function test_suspended_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'status' => 'suspended',
            'password' => bcrypt('Password1!'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'status' => 'deactivated',
            'password' => bcrypt('Password1!'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_pending_user_can_still_log_in(): void
    {
        $user = User::factory()->create([
            'status' => 'pending',
            'password' => bcrypt('Password1!'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_active_user_can_log_in(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'password' => bcrypt('Password1!'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    private function pendingBuyer(): User
    {
        $buyer = User::factory()->create([
            'role' => UserRole::BUYER->value,
            'status' => 'pending',
        ]);

        $buyer->buyerProfile()->create([
            'business_name' => 'Test Trading Co.',
            'contact_person' => 'Juan Dela Cruz',
            'phone' => '09171234567',
            'business_address' => '123 Market St.',
            'is_verified' => false,
        ]);

        return $buyer;
    }

    public function test_approving_a_buyer_activates_their_account_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);
        $buyer = $this->pendingBuyer();

        $response = $this->actingAs($admin)->post(route('admin.buyers.approve', $buyer));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $buyer->id, 'status' => 'active']);
        $this->assertDatabaseHas('buyer_profiles', ['user_id' => $buyer->id, 'is_verified' => true]);
    }

    public function test_rejecting_a_buyer_reverts_their_account_status_to_pending(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);
        $buyer = $this->pendingBuyer();
        $buyer->update(['status' => 'active']);
        $buyer->buyerProfile()->update(['is_verified' => true]);

        $response = $this->actingAs($admin)->post(route('admin.buyers.reject', $buyer));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $buyer->id, 'status' => 'pending']);
        $this->assertDatabaseHas('buyer_profiles', ['user_id' => $buyer->id, 'is_verified' => false]);
    }
}
