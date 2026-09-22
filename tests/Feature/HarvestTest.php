<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\Harvest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HarvestTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedFarmer(): User
    {
        $user = User::factory()->farmer()->create([
            'email_verified_at' => now(),
        ]);
        $user->farmerProfile()->create([
            'phone' => '09123456789',
            'farm_location' => 'Test Farm, Davao',
            'is_verified' => true,
            'latitude' => 7.0,
            'longitude' => 125.5,
            'affiliation_type' => 'independent',
        ]);

        return $user;
    }

    private function createCropVariety(): array
    {
        $category = CropCategory::create([
            'name' => 'Test Category',
            'status' => 'active',
        ]);
        $crop = Crop::create([
            'crop_category_id' => $category->id,
            'name' => 'Rice',
            'status' => 'active',
        ]);
        $variety = CropVariety::create([
            'crop_id' => $crop->id,
            'name' => 'IR64',
            'status' => 'active',
        ]);

        return ['crop' => $crop, 'variety' => $variety];
    }

    // ─── AUTHORIZATION ───────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_harvests(): void
    {
        $this->get('/harvests')->assertRedirect('/login');
    }

    public function test_non_farmer_cannot_access_harvests(): void
    {
        $user = User::factory()->buyer()->create(['email_verified_at' => now()]);
        $this->actingAs($user)->get('/harvests')->assertStatus(403);
    }

    public function test_unverified_farmer_is_redirected_on_create(): void
    {
        $user = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $user->farmerProfile()->create([
            'phone' => '09123456789',
            'is_verified' => false,
            'affiliation_type' => 'independent',
            'latitude' => 6.1164,
            'longitude' => 125.1716,
        ]);

        $this->actingAs($user)
            ->get('/harvests/create')
            ->assertRedirect(route('harvests.index'));
    }

    // ─── INDEX ───────────────────────────────────────────────

    public function test_farmer_can_view_harvest_index(): void
    {
        $user = $this->createVerifiedFarmer();

        $response = $this->actingAs($user)->get('/harvests');
        $response->assertStatus(200);
    }

    // ─── STORE ───────────────────────────────────────────────

    public function test_verified_farmer_can_store_harvest(): void
    {
        $user = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $payload = [
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'quantity_kg' => 500,
            'destination_address' => 'Davao City Market',
            'destination_latitude' => 7.07,
            'destination_longitude' => 125.61,
        ];

        $this->actingAs($user)->post('/harvests', $payload)
            ->assertRedirect(route('harvests.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('harvests', [
            'user_id' => $user->id,
            'crop_id' => $crop->id,
            'quantity_kg' => 500,
            'destination_latitude' => 7.07,
            'destination_longitude' => 125.61,
        ]);
    }

    public function test_coop_farmer_harvest_destination_is_forced_to_coop_hub(): void
    {
        $coop = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $coop->logisticsProfile()->create([
            'company_name' => 'Agri Coop Hub',
            'business_permit_no' => 'BL-99999',
            'phone' => '09123456789',
            'is_verified' => true,
            'logistics_type' => 'cooperative',
            'office_address' => 'Coop Drop-off, Gensan',
            'latitude' => 6.1050,
            'longitude' => 125.1830,
        ]);

        $user = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $user->farmerProfile()->create([
            'phone' => '09123456789',
            'farm_location' => 'Test Farm, Davao',
            'is_verified' => true,
            'latitude' => 7.0,
            'longitude' => 125.5,
            'affiliation_type' => 'cooperative',
            'cooperative_id' => $coop->id,
            'membership_status' => 'approved',
        ]);
        // Sync users table cooperative_id like the app does
        $user->update(['cooperative_id' => $coop->id, 'affiliation_type' => 'cooperative']);

        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $payload = [
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'quantity_kg' => 500,
            // A coop farmer's form has no destination fields; even if tampered with, the backend forces the coop hub.
            'destination_address' => 'Hacked Destination',
            'destination_latitude' => 8.0,
            'destination_longitude' => 126.0,
        ];

        $this->actingAs($user)->post('/harvests', $payload)
            ->assertRedirect(route('harvests.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('harvests', [
            'user_id' => $user->id,
            'crop_id' => $crop->id,
            'quantity_kg' => 500,
            'destination_id' => null,
            'destination_address' => 'Coop Drop-off, Gensan',
            'destination_latitude' => 6.1050,
            'destination_longitude' => 125.1830,
        ]);
    }

    public function test_coop_farmer_with_pinned_hub_posts_without_destination_fields(): void
    {
        $coop = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $coop->logisticsProfile()->create([
            'company_name' => 'Agri Coop Hub',
            'business_permit_no' => 'BL-99998',
            'phone' => '09181234567',
            'is_verified' => true,
            'logistics_type' => 'cooperative',
            'office_address' => 'Coop Drop-off, Gensan',
            'latitude' => 6.1050,
            'longitude' => 125.1830,
        ]);

        $user = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $user->farmerProfile()->create([
            'phone' => '09181234567',
            'farm_location' => 'Test Farm, Davao',
            'is_verified' => true,
            'latitude' => 7.0,
            'longitude' => 125.5,
            'affiliation_type' => 'cooperative',
            'cooperative_id' => $coop->id,
            'membership_status' => 'approved',
        ]);
        $user->update(['cooperative_id' => $coop->id, 'affiliation_type' => 'cooperative']);

        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        // The coop form does not send destination fields; the server fills the hub.
        $payload = [
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'quantity_kg' => 500,
        ];

        $this->actingAs($user)->post('/harvests', $payload)
            ->assertRedirect(route('harvests.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('harvests', [
            'user_id' => $user->id,
            'crop_id' => $crop->id,
            'quantity_kg' => 500,
            'destination_id' => null,
            'destination_address' => 'Coop Drop-off, Gensan',
            'destination_latitude' => 6.1050,
            'destination_longitude' => 125.1830,
        ]);
    }

    public function test_verified_farmer_can_store_harvest_in_sacks(): void
    {
        $user = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $payload = [
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'quantity_kg' => 100,
            'unit' => 'sacks',
            'suggested_price_per_kg' => 30,
            'destination_address' => 'Davao City Market',
            'destination_latitude' => 7.07,
            'destination_longitude' => 125.61,
        ];

        $this->actingAs($user)->post('/harvests', $payload)
            ->assertRedirect(route('harvests.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('harvests', [
            'user_id' => $user->id,
            'crop_id' => $crop->id,
            'unit' => 'sacks',
            'quantity_kg' => 5000,
            'suggested_price_per_kg' => 0.60,
        ]);
    }

    public function test_harvest_date_can_be_far_in_the_future(): void
    {
        $user = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $payload = [
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'quantity_kg' => 500,
            'destination_address' => 'Davao City Market',
            'destination_latitude' => 7.07,
            'destination_longitude' => 125.61,
            'harvest_date' => now()->addMonths(4)->format('Y-m-d'),
        ];

        $this->actingAs($user)->post('/harvests', $payload)
            ->assertRedirect(route('harvests.index'))
            ->assertSessionHas('success');
    }

    public function test_store_validates_required_fields(): void
    {
        $user = $this->createVerifiedFarmer();

        $this->actingAs($user)->post('/harvests', [])
            ->assertSessionHasErrors(['crop_id', 'crop_variety_id', 'quantity_kg', 'destination_address', 'destination_latitude', 'destination_longitude']);
    }

    public function test_coop_farmer_without_coop_location_is_blocked_from_posting(): void
    {
        $coop = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $coop->logisticsProfile()->create([
            'company_name' => 'No Pin Coop',
            'business_permit_no' => 'BL-00001',
            'phone' => '09123456789',
            'is_verified' => true,
            'logistics_type' => 'cooperative',
            'office_address' => 'Somewhere, Gensan',
        ]);

        $user = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $user->farmerProfile()->create([
            'phone' => '09123456789',
            'farm_location' => 'Test Farm, Davao',
            'is_verified' => true,
            'latitude' => 7.0,
            'longitude' => 125.5,
            'affiliation_type' => 'cooperative',
            'cooperative_id' => $coop->id,
            'membership_status' => 'approved',
        ]);
        $user->update(['cooperative_id' => $coop->id, 'affiliation_type' => 'cooperative']);

        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $response = $this->actingAs($user)->post('/harvests', [
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'quantity_kg' => 500,
        ]);

        $response->assertSessionHasErrors('destination_address');
        $response->assertSessionHasErrors([
            'destination_address' => 'Your cooperative hasn\'t set its drop-off location yet. Ask your cooperative administrator to pin it in the Cooperative Location section before posting a harvest.',
        ]);

        $this->assertDatabaseMissing('harvests', ['user_id' => $user->id]);
    }

    public function test_store_validates_crop_exists(): void
    {
        $user = $this->createVerifiedFarmer();

        $this->actingAs($user)->post('/harvests', [
            'crop_id' => 99999,
            'crop_variety_id' => 1,
            'quantity_kg' => 100,
            'destination_address' => 'Test',
            'destination_latitude' => 7.0,
            'destination_longitude' => 125.0,
        ])->assertSessionHasErrors('crop_id');
    }

    public function test_store_validates_destination_within_philippines(): void
    {
        $user = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $this->actingAs($user)->post('/harvests', [
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'quantity_kg' => 100,
            'destination_address' => 'Somewhere',
            'destination_latitude' => 50.0,   // outside PH (4-21N)
            'destination_longitude' => 125.0,
        ])->assertSessionHas('error');
    }

    public function test_store_validates_quantity_min(): void
    {
        $user = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $this->actingAs($user)->post('/harvests', [
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'quantity_kg' => 0,
            'destination_address' => 'Test',
            'destination_latitude' => 7.0,
            'destination_longitude' => 125.0,
        ])->assertSessionHasErrors('quantity_kg');
    }

    public function test_store_validates_variety_belongs_to_crop(): void
    {
        $user = $this->createVerifiedFarmer();
        $category = CropCategory::create(['name' => 'Cat A', 'status' => 'active']);
        $cropA = Crop::create(['crop_category_id' => $category->id, 'name' => 'Rice', 'status' => 'active']);
        $cropB = Crop::create(['crop_category_id' => $category->id, 'name' => 'Corn', 'status' => 'active']);
        $variety = CropVariety::create(['crop_id' => $cropA->id, 'name' => 'IR64', 'status' => 'active']);

        $this->actingAs($user)->post('/harvests', [
            'crop_id' => $cropB->id,
            'crop_variety_id' => $variety->id,
            'quantity_kg' => 100,
            'destination_address' => 'Test',
            'destination_latitude' => 7.0,
            'destination_longitude' => 125.0,
        ])->assertSessionHas('error');
    }

    // ─── UPDATE ──────────────────────────────────────────────

    public function test_farmer_can_update_own_harvest(): void
    {
        $user = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $harvest = Harvest::create([
            'user_id' => $user->id,
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'crop_category_id' => $crop->crop_category_id,
            'crop_type' => $crop->name,
            'variety' => $variety->name,
            'quantity_kg' => 500,
            'remaining_quantity_kg' => 500,
            'unit' => 'kg',
            'status' => 'active',
            'destination_address' => 'Test',
            'destination_latitude' => 7.0,
            'destination_longitude' => 125.0,
        ]);

        $this->actingAs($user)->put("/harvests/{$harvest->id}", [
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'quantity_kg' => 750,
        ])->assertRedirect(route('harvests.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('harvests', [
            'id' => $harvest->id,
            'quantity_kg' => 750,
        ]);
    }

    public function test_farmer_cannot_update_other_users_harvest(): void
    {
        $owner = $this->createVerifiedFarmer();
        $other = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $harvest = Harvest::create([
            'user_id' => $owner->id,
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'crop_category_id' => $crop->crop_category_id,
            'crop_type' => $crop->name,
            'variety' => $variety->name,
            'quantity_kg' => 500,
            'remaining_quantity_kg' => 500,
            'unit' => 'kg',
            'status' => 'active',
            'destination_address' => 'Test',
            'destination_latitude' => 7.0,
            'destination_longitude' => 125.0,
        ]);

        $this->actingAs($other)->put("/harvests/{$harvest->id}", [
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'quantity_kg' => 999,
        ])->assertStatus(403);
    }

    // ─── DESTROY ─────────────────────────────────────────────

    public function test_farmer_can_delete_own_harvest(): void
    {
        $user = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $harvest = Harvest::create([
            'user_id' => $user->id,
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'crop_category_id' => $crop->crop_category_id,
            'crop_type' => $crop->name,
            'variety' => $variety->name,
            'quantity_kg' => 500,
            'remaining_quantity_kg' => 500,
            'unit' => 'kg',
            'status' => 'active',
            'destination_address' => 'Test',
            'destination_latitude' => 7.0,
            'destination_longitude' => 125.0,
        ]);

        $this->actingAs($user)->delete("/harvests/{$harvest->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('harvests', ['id' => $harvest->id]);
    }

    public function test_farmer_cannot_delete_other_users_harvest(): void
    {
        $owner = $this->createVerifiedFarmer();
        $other = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $harvest = Harvest::create([
            'user_id' => $owner->id,
            'crop_id' => $crop->id,
            'crop_variety_id' => $variety->id,
            'crop_category_id' => $crop->crop_category_id,
            'crop_type' => $crop->name,
            'variety' => $variety->name,
            'quantity_kg' => 500,
            'remaining_quantity_kg' => 500,
            'unit' => 'kg',
            'status' => 'active',
            'destination_address' => 'Test',
            'destination_latitude' => 7.0,
            'destination_longitude' => 125.0,
        ]);

        $this->actingAs($other)->delete("/harvests/{$harvest->id}")
            ->assertStatus(403);
    }
}
