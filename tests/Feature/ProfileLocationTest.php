<?php

namespace Tests\Feature;

use App\Models\BuyerProfile;
use App\Models\Cooperative;
use App\Models\FarmerProfile;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfileLocationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGeocoding(?string $address = 'Purok 3, Barangay Fatima, General Santos City'): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => $address
                ? Http::response(['display_name' => $address], 200)
                : Http::response([], 500),
        ]);
    }

    public function test_farmer_first_time_location_save_does_not_require_a_password(): void
    {
        $this->fakeGeocoding();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);

        $response = $this->actingAs($farmer)->post(route('profile.save-location'), [
            'latitude' => 6.1164,
            'longitude' => 125.1716,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('farmer_profiles', ['user_id' => $farmer->id, 'latitude' => 6.1164]);
    }

    public function test_farmer_first_time_save_auto_fills_the_geocoded_address(): void
    {
        $this->fakeGeocoding('Purok 3, Barangay Fatima, General Santos City');
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);

        $this->actingAs($farmer)->post(route('profile.save-location'), [
            'latitude' => 6.1164,
            'longitude' => 125.1716,
        ]);

        $this->assertDatabaseHas('farmer_profiles', [
            'user_id' => $farmer->id,
            'farm_location' => 'Purok 3, Barangay Fatima, General Santos City',
        ]);
    }

    public function test_farmer_changing_an_existing_location_requires_the_password(): void
    {
        $this->fakeGeocoding();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);
        FarmerProfile::create([
            'user_id' => $farmer->id,
            'latitude' => 6.1000,
            'longitude' => 125.1000,
            'farm_location' => 'Old Spot',
        ]);

        $response = $this->actingAs($farmer)->post(route('profile.save-location'), [
            'latitude' => 6.2000,
            'longitude' => 125.2000,
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseHas('farmer_profiles', ['user_id' => $farmer->id, 'latitude' => 6.1000]);
    }

    public function test_farmer_changing_an_existing_location_succeeds_with_the_correct_password(): void
    {
        $this->fakeGeocoding('New Address');
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);
        FarmerProfile::create([
            'user_id' => $farmer->id,
            'latitude' => 6.1000,
            'longitude' => 125.1000,
            'farm_location' => 'Old Spot',
        ]);

        $response = $this->actingAs($farmer)->post(route('profile.save-location'), [
            'latitude' => 6.2000,
            'longitude' => 125.2000,
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('farmer_profiles', ['user_id' => $farmer->id, 'latitude' => 6.2000]);
    }

    public function test_farmer_changing_an_existing_location_with_the_wrong_password_is_rejected(): void
    {
        $this->fakeGeocoding();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);
        FarmerProfile::create([
            'user_id' => $farmer->id,
            'latitude' => 6.1000,
            'longitude' => 125.1000,
        ]);

        $response = $this->actingAs($farmer)->post(route('profile.save-location'), [
            'latitude' => 6.2000,
            'longitude' => 125.2000,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertDatabaseHas('farmer_profiles', ['user_id' => $farmer->id, 'latitude' => 6.1000]);
    }

    public function test_a_custom_label_is_not_overwritten_by_the_geocoded_address(): void
    {
        $this->fakeGeocoding('Geocoded Address');
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);

        $this->actingAs($farmer)->post(route('profile.save-location'), [
            'latitude' => 6.1164,
            'longitude' => 125.1716,
            'location_label' => 'My Own Custom Label',
        ]);

        $this->assertDatabaseHas('farmer_profiles', [
            'user_id' => $farmer->id,
            'farm_location' => 'My Own Custom Label',
        ]);
    }

    public function test_geocoding_failure_does_not_block_saving_the_pin(): void
    {
        $this->fakeGeocoding(null);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);

        $response = $this->actingAs($farmer)->post(route('profile.save-location'), [
            'latitude' => 6.1164,
            'longitude' => 125.1716,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('farmer_profiles', ['user_id' => $farmer->id, 'latitude' => 6.1164]);
    }

    public function test_cooperative_admin_can_save_a_location(): void
    {
        $this->fakeGeocoding();
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);

        $response = $this->actingAs($admin)->post(route('profile.save-location'), [
            'latitude' => 6.1164,
            'longitude' => 125.1716,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('cooperatives', ['id' => $coop->id, 'latitude' => '6.1164']);
    }

    public function test_buyer_can_save_a_location_which_previously_silently_did_nothing(): void
    {
        $this->fakeGeocoding('Buyer Address');
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);

        $response = $this->actingAs($buyer)->post(route('profile.save-location'), [
            'latitude' => 6.1164,
            'longitude' => 125.1716,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('buyer_profiles', [
            'user_id' => $buyer->id,
            'latitude' => 6.1164,
            'location_label' => 'Buyer Address',
        ]);
    }

    public function test_buyer_changing_an_existing_location_requires_the_password(): void
    {
        $this->fakeGeocoding();
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
        BuyerProfile::create([
            'user_id' => $buyer->id,
            'business_name' => 'Metro Fresh',
            'latitude' => 6.1000,
            'longitude' => 125.1000,
        ]);

        $response = $this->actingAs($buyer)->post(route('profile.save-location'), [
            'latitude' => 6.2000,
            'longitude' => 125.2000,
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_profile_page_renders_visible_editable_coordinate_inputs(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);

        $response = $this->actingAs($farmer)->get(route('profile.show'));

        $response->assertOk();
        $response->assertSee('name="latitude"', false);
        $response->assertSee('name="longitude"', false);
        $response->assertSee('type="number"', false);
    }

    public function test_other_location_pickers_still_render_coordinates_as_hidden_inputs(): void
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop-hidden@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);
        FarmerProfile::create([
            'user_id' => $farmer->id,
            'affiliation_type' => 'cooperative',
            'cooperative_id' => $coop->id,
            'membership_status' => 'approved',
        ]);

        $response = $this->actingAs($farmer)->get(route('farmer.haul-requests.create'));

        $response->assertOk();
        $response->assertSee('type="hidden" name="pickup_location_lat"', false);
    }
}
