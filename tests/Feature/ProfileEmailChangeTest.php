<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProfileEmailChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_email_requires_the_password(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'old@example.com']);

        $response = $this->actingAs($user)->put(route('profile.email'), [
            'email' => 'new@example.com',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'old@example.com']);
    }

    public function test_wrong_password_is_rejected(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'old@example.com']);

        $response = $this->actingAs($user)->put(route('profile.email'), [
            'email' => 'new@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'old@example.com']);
    }

    public function test_changing_email_with_the_correct_password_updates_it_and_sends_a_new_otp(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'old@example.com', 'email_verified_at' => now()]);

        $response = $this->actingAs($user)->put(route('profile.email'), [
            'email' => 'new@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $user->refresh();
        $this->assertEquals('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->email_otp);
        Mail::assertSent(\App\Mail\SendOtpMail::class);
    }

    public function test_the_rest_of_the_app_is_locked_until_the_new_email_is_reverified(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'old@example.com', 'email_verified_at' => now()]);

        $this->actingAs($user)->put(route('profile.email'), [
            'email' => 'new@example.com',
            'password' => 'password',
        ]);

        $user->refresh();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_the_user_can_still_reach_their_own_profile_page_while_unverified(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'old@example.com', 'email_verified_at' => now()]);

        $this->actingAs($user)->put(route('profile.email'), [
            'email' => 'new@example.com',
            'password' => 'password',
        ]);

        $user->refresh();
        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertOk();
    }

    public function test_new_email_must_be_unique(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'old@example.com']);

        $response = $this->actingAs($user)->put(route('profile.email'), [
            'email' => 'taken@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'old@example.com']);
    }

    public function test_name_and_phone_update_still_needs_no_password(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'New Name',
            'phone' => '09171234567',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }
}
