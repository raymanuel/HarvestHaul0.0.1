<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_displayed(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unverified_user_can_login(): void
    {
        $user = User::factory()->unverified()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_unverified_user_is_blocked_from_dashboard(): void
    {
        $user = User::factory()->unverified()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_is_blocked_from_feature_routes(): void
    {
        $user = User::factory()->farmer()->unverified()->create();

        $response = $this->actingAs($user)->get('/harvests/create');

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_user_can_access_dashboard_after_otp_verification(): void
    {
        $user = User::factory()->unverified()->create();
        $user->forceFill([
            'email_otp'            => '123456',
            'email_otp_expires_at' => now()->addMinutes(10),
        ])->save();

        $response = $this->actingAs($user)->post('/email/verify-otp', [
            'otp' => '123456',
        ]);

        $response->assertRedirect();
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->actingAs($user)->get('/dashboard')->assertStatus(200);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        // Attempt 15 times (limit is 15 per minute)
        for ($i = 0; $i < 15; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }
}
