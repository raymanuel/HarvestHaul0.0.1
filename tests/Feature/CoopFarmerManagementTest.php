<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\FarmerProfile;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoopFarmerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function coopAdmin(): User
    {
        $cooperative = Cooperative::create([
            'name'           => 'GenSan AgCoop',
            'type'           => 'primary',
            'contact_number' => '09171234567',
            'official_email' => 'coop@example.com',
            'status'         => Cooperative::STATUS_APPROVED,
        ]);

        return User::factory()->create([
            'role'              => UserRole::COOP_ADMIN->value,
            'cooperative_id'    => $cooperative->id,
            'email_verified_at' => now(),
        ]);
    }

    public function test_coop_admin_can_add_a_farmer_directly(): void
    {
        $coop = $this->coopAdmin();

        $response = $this->actingAs($coop)->post(route('coop.farmers.store'), [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'Password1!',
            'phone' => '09171112222',
            'farm_location' => 'Purok 3, San Isidro',
            'latitude' => 7.0,
            'longitude' => 125.5,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'juan@example.com',
            'role' => UserRole::FARMER->value,
            'cooperative_id' => $coop->cooperative_id,
        ]);

        $farmer = User::where('email', 'juan@example.com')->first();

        $this->assertDatabaseHas('farmer_profiles', [
            'user_id' => $farmer->id,
            'cooperative_id' => $coop->cooperative_id,
            'membership_status' => 'approved',
        ]);
    }

    public function test_coop_admin_cannot_view_a_farmer_from_another_cooperative(): void
    {
        $coopA = $this->coopAdmin();
        $coopB = $this->coopAdmin();

        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);
        FarmerProfile::create([
            'user_id' => $farmer->id,
            'cooperative_id' => $coopB->cooperative_id,
            'affiliation_type' => 'cooperative',
            'membership_status' => 'approved',
            'membership_decided_at' => now(),
        ]);

        $response = $this->actingAs($coopA)->get(route('coop.farmers.show', $farmer));

        $response->assertForbidden();
    }

    public function test_coop_admin_cannot_edit_a_farmer_from_another_cooperative(): void
    {
        $coopA = $this->coopAdmin();
        $coopB = $this->coopAdmin();

        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);
        FarmerProfile::create([
            'user_id' => $farmer->id,
            'cooperative_id' => $coopB->cooperative_id,
            'affiliation_type' => 'cooperative',
            'membership_status' => 'approved',
            'membership_decided_at' => now(),
        ]);

        $response = $this->actingAs($coopA)->put(route('coop.farmers.update', $farmer), [
            'name' => 'Hacked Name',
            'email' => 'hacked@example.com',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'hacked@example.com']);
    }

    public function test_coop_admin_can_edit_own_farmer_details(): void
    {
        $coop = $this->coopAdmin();

        $farmerUser = User::factory()->create([
            'role' => UserRole::FARMER->value,
            'cooperative_id' => $coop->cooperative_id,
            'name' => 'Old Name',
        ]);
        FarmerProfile::create([
            'user_id' => $farmerUser->id,
            'cooperative_id' => $coop->cooperative_id,
            'affiliation_type' => 'cooperative',
            'membership_status' => 'approved',
            'membership_decided_at' => now(),
        ]);

        $response = $this->actingAs($coop)->put(route('coop.farmers.update', $farmerUser), [
            'name' => 'New Name',
            'email' => $farmerUser->email,
            'farm_location' => 'Updated Location',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $farmerUser->id, 'name' => 'New Name']);
        $this->assertDatabaseHas('farmer_profiles', ['user_id' => $farmerUser->id, 'farm_location' => 'Updated Location']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'update_farmer', 'target_id' => $farmerUser->id]);
    }

    /**
     * Spec 3.5: "Farmer location changes should be audited because they
     * affect routing." ProfileController::saveLocation() previously wrote
     * no audit record at all.
     */
    public function test_farmer_saving_pickup_location_is_audited(): void
    {
        $farmerUser = User::factory()->create(['role' => UserRole::FARMER->value]);
        FarmerProfile::create([
            'user_id' => $farmerUser->id,
            'affiliation_type' => 'independent',
        ]);

        $response = $this->actingAs($farmerUser)->post(route('profile.save-location'), [
            'latitude' => 7.05,
            'longitude' => 125.55,
            'location_label' => 'New Farm Spot',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('farmer_profiles', [
            'user_id' => $farmerUser->id,
            'latitude' => 7.05,
            'longitude' => 125.55,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $farmerUser->id,
            'action' => 'update_location',
            'target_type' => 'farmer_profile',
        ]);
    }
}
