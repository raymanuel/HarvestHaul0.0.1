<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LogisticsProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_displayed(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_user_can_register_as_farmer(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'phone' => '09123456789',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'farmer',
            'accepted_terms' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'farmer@example.com',
            'role' => 'farmer',
        ]);
    }

    public function test_user_can_register_as_buyer(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Buyer',
            'email' => 'buyer@example.com',
            'phone' => '09123456789',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'buyer',
            'accepted_terms' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'buyer@example.com',
            'role' => 'buyer',
        ]);
    }

    public function test_user_can_register_as_logistics_partner(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Logistics',
            'email' => 'logistics@example.com',
            'phone' => '09123456789',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'logistics_partner',
            'logistics_type' => 'company',
            'company_name' => 'Test Company',
            'accepted_terms' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'logistics@example.com',
            'role' => 'logistics_partner',
        ]);
    }

    public function test_user_cannot_register_with_invalid_data(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'role' => 'invalid',
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_user_cannot_register_with_existing_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'phone' => '09123456789',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'farmer',
            'accepted_terms' => '1',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_cannot_register_without_terms_acceptance(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '09123456789',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'farmer',
        ]);

        $response->assertSessionHasErrors('accepted_terms');
    }

    public function test_farmer_can_register_with_cooperative(): void
    {
        $cooperative = LogisticsProfile::factory()->cooperative()->verified()->create();

        $response = $this->post('/register', [
            'name' => 'Coop Farmer',
            'email' => 'coopfarmer@example.com',
            'phone' => '09123456789',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'farmer',
            'affiliation_type' => 'cooperative',
            'cooperative_id' => $cooperative->id,
            'accepted_terms' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'coopfarmer@example.com',
            'role' => 'farmer',
        ]);
    }

    public function test_farmer_cannot_register_with_invalid_cooperative(): void
    {
        $response = $this->post('/register', [
            'name' => 'Bad Coop Farmer',
            'email' => 'badcoop@example.com',
            'phone' => '09123456789',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'farmer',
            'affiliation_type' => 'cooperative',
            'cooperative_id' => 99999,
            'accepted_terms' => '1',
        ]);

        $response->assertSessionHasErrors('cooperative_id');
    }
}
