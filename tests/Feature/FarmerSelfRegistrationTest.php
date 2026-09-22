<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\FarmerProfile;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmerSelfRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function approvedCoop(): Cooperative
    {
        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
    }

    public function test_farmer_can_self_register_without_a_premature_profile(): void
    {
        $response = $this->post(route('register.store'), [
            'role' => 'farmer',
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '09171234567',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'accepted_terms' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'juan@example.com', 'role' => UserRole::FARMER->value]);

        $user = User::where('email', 'juan@example.com')->first();
        $this->assertDatabaseMissing('farmer_profiles', ['user_id' => $user->id]);
    }

    public function test_registration_requires_a_name(): void
    {
        $response = $this->post(route('register.store'), [
            'role' => 'farmer',
            'email' => 'noname@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'accepted_terms' => '1',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseMissing('users', ['email' => 'noname@example.com']);
    }

    public function test_unaffiliated_farmer_sees_a_real_join_cooperative_link(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);

        $response = $this->actingAs($farmer)->get(route('farmer.dashboard'));

        $response->assertOk();
        $response->assertSee(route('farmer.join-cooperative.create'), false);

        $response = $this->actingAs($farmer)->get(route('farmer.haul-requests.index'));
        $response->assertOk();
        $response->assertSee(route('farmer.join-cooperative.create'), false);
    }

    public function test_only_approved_cooperatives_appear_in_the_picker(): void
    {
        $approved = $this->approvedCoop();
        $pending = Cooperative::create([
            'name' => 'Pending Coop', 'type' => 'primary',
            'contact_number' => '09170000000', 'official_email' => 'pending@example.com',
            'status' => Cooperative::STATUS_PENDING,
        ]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);

        $response = $this->actingAs($farmer)->get(route('farmer.join-cooperative.create'));

        $response->assertOk();
        $response->assertSee($approved->name);
        $response->assertDontSee($pending->name);
    }

    public function test_farmer_can_request_to_join_a_cooperative_and_admin_is_notified(): void
    {
        $coop = $this->approvedCoop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'name' => 'Ana Reyes']);

        $response = $this->actingAs($farmer)->post(route('farmer.join-cooperative.store'), [
            'cooperative_id' => $coop->id,
            'phone' => '09171234567',
            'farm_location' => 'Barangay Fatima',
        ]);

        $response->assertRedirect(route('farmer.dashboard'));

        $profile = FarmerProfile::where('user_id', $farmer->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals('pending', $profile->membership_status);
        $this->assertEquals($coop->id, $profile->cooperative_id);
        $this->assertNotNull($profile->membership_requested_at);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'category' => 'membership',
        ]);
    }

    public function test_a_pending_request_cannot_be_resubmitted(): void
    {
        $coop = $this->approvedCoop();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);
        FarmerProfile::create([
            'user_id' => $farmer->id,
            'cooperative_id' => $coop->id,
            'affiliation_type' => 'cooperative',
            'membership_status' => 'pending',
            'membership_requested_at' => now(),
        ]);

        $getResponse = $this->actingAs($farmer)->get(route('farmer.join-cooperative.create'));
        $getResponse->assertRedirect(route('farmer.dashboard'));

        $postResponse = $this->actingAs($farmer)->post(route('farmer.join-cooperative.store'), [
            'cooperative_id' => $coop->id,
        ]);
        $postResponse->assertSessionHasErrors(['cooperative_id']);
    }

    public function test_an_approved_member_cannot_resubmit_a_join_request(): void
    {
        $coop = $this->approvedCoop();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);
        FarmerProfile::create([
            'user_id' => $farmer->id,
            'cooperative_id' => $coop->id,
            'affiliation_type' => 'cooperative',
            'membership_status' => 'approved',
            'membership_requested_at' => now(),
            'membership_decided_at' => now(),
        ]);

        $response = $this->actingAs($farmer)->get(route('farmer.join-cooperative.create'));
        $response->assertRedirect(route('farmer.dashboard'));
    }

    public function test_full_loop_register_request_approve_then_submit_haul_request(): void
    {
        $coop = $this->approvedCoop();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);

        // 1. Register
        $this->post(route('register.store'), [
            'role' => 'farmer',
            'name' => 'Pedro Cruz',
            'email' => 'pedro@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'accepted_terms' => '1',
        ])->assertRedirect();

        $farmer = User::where('email', 'pedro@example.com')->first();
        $farmer->forceFill(['email_verified_at' => now()])->save();

        // 2. Request to join
        $this->actingAs($farmer)->post(route('farmer.join-cooperative.store'), [
            'cooperative_id' => $coop->id,
        ])->assertRedirect(route('farmer.dashboard'));

        // 3. Coop admin approves via the existing queue
        $this->actingAs($admin)->post(route('coop.farmers.approve', $farmer->id))
            ->assertRedirect();

        $profile = FarmerProfile::where('user_id', $farmer->id)->first();
        $this->assertEquals('approved', $profile->membership_status);

        // 4. Farmer can now submit a haul request. Re-fetch the user: actingAs()
        // reuses the same PHP object across calls within a test method, and its
        // farmerProfile relation was already lazy-loaded (and cached) as null in
        // step 2, before the profile row existed.
        $farmer->refresh();
        $farmer->unsetRelation('farmerProfile');
        $response = $this->actingAs($farmer)->post(route('farmer.haul-requests.store'), [
            'crop_id' => \App\Models\Crop::factory()->create()->id,
            'estimated_sacks' => 10,
            'estimated_weight_kg' => 500,
            'preferred_pickup_date' => now()->addDays(3)->format('Y-m-d'),
            'pickup_window_start' => '08:00',
            'pickup_window_end' => '10:00',
            'pickup_location' => 'Barangay Fatima',
            'pickup_location_lat' => 6.1164,
            'pickup_location_lng' => 125.1716,
        ]);

        $this->assertDatabaseHas('haul_requests', ['farmer_id' => $farmer->id]);
    }
}
