<?php

namespace Tests\Feature;

use App\Models\BuyerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'role' => 'buyer',
            'business_name' => 'Metro Fresh Produce',
            'contact_person' => 'Juan Dela Cruz',
            'email' => 'metrofresh@example.com',
            'phone' => '09171234567',
            'business_address' => '123 Market St, Davao City',
            'business_information' => 'We supply grocery chains across Mindanao.',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'accepted_terms' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'metrofresh@example.com', 'role' => 'buyer']);
        $this->assertDatabaseHas('buyer_profiles', [
            'business_name' => 'Metro Fresh Produce',
            'business_information' => 'We supply grocery chains across Mindanao.',
            'status' => BuyerProfile::STATUS_PENDING,
        ]);
    }

    public function test_buyer_registration_requires_business_fields(): void
    {
        $response = $this->post(route('register.store'), [
            'role' => 'buyer',
            'email' => 'incomplete@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'accepted_terms' => '1',
        ]);

        $response->assertSessionHasErrors(['business_name', 'contact_person', 'phone', 'business_address']);
        $this->assertDatabaseMissing('users', ['email' => 'incomplete@example.com']);
    }
}
