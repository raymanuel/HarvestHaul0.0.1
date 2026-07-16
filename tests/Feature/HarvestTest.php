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
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'quantity_kg'           => 500,
            'destination_address'   => 'Davao City Market',
            'destination_latitude'  => 7.07,
            'destination_longitude' => 125.61,
        ];

        $this->actingAs($user)->post('/harvests', $payload)
            ->assertRedirect(route('harvests.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('harvests', [
            'user_id'    => $user->id,
            'crop_id'    => $crop->id,
            'quantity_kg' => 500,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = $this->createVerifiedFarmer();

        $this->actingAs($user)->post('/harvests', [])
            ->assertSessionHasErrors(['crop_id', 'crop_variety_id', 'quantity_kg', 'destination_address', 'destination_latitude', 'destination_longitude']);
    }

    public function test_store_validates_crop_exists(): void
    {
        $user = $this->createVerifiedFarmer();

        $this->actingAs($user)->post('/harvests', [
            'crop_id'               => 99999,
            'crop_variety_id'       => 1,
            'quantity_kg'           => 100,
            'destination_address'   => 'Test',
            'destination_latitude'  => 7.0,
            'destination_longitude' => 125.0,
        ])->assertSessionHasErrors('crop_id');
    }

    public function test_store_validates_destination_within_philippines(): void
    {
        $user = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $this->actingAs($user)->post('/harvests', [
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'quantity_kg'           => 100,
            'destination_address'   => 'Somewhere',
            'destination_latitude'  => 50.0,   // outside PH (4-21N)
            'destination_longitude' => 125.0,
        ])->assertSessionHas('error');
    }

    public function test_store_validates_quantity_min(): void
    {
        $user = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $this->actingAs($user)->post('/harvests', [
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'quantity_kg'           => 0,
            'destination_address'   => 'Test',
            'destination_latitude'  => 7.0,
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
            'crop_id'               => $cropB->id,
            'crop_variety_id'       => $variety->id,
            'quantity_kg'           => 100,
            'destination_address'   => 'Test',
            'destination_latitude'  => 7.0,
            'destination_longitude' => 125.0,
        ])->assertSessionHas('error');
    }

    // ─── UPDATE ──────────────────────────────────────────────

    public function test_farmer_can_update_own_harvest(): void
    {
        $user = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $harvest = Harvest::create([
            'user_id'                => $user->id,
            'crop_id'                => $crop->id,
            'crop_variety_id'        => $variety->id,
            'crop_category_id'       => $crop->crop_category_id,
            'crop_type'              => $crop->name,
            'variety'                => $variety->name,
            'quantity_kg'            => 500,
            'remaining_quantity_kg'  => 500,
            'unit'                   => 'kg',
            'status'                 => 'active',
            'destination_address'    => 'Test',
            'destination_latitude'   => 7.0,
            'destination_longitude'  => 125.0,
        ]);

        $this->actingAs($user)->put("/harvests/{$harvest->id}", [
            'crop_id'         => $crop->id,
            'crop_variety_id' => $variety->id,
            'quantity_kg'     => 750,
        ])->assertRedirect(route('harvests.index'))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('harvests', [
            'id'          => $harvest->id,
            'quantity_kg' => 750,
        ]);
    }

    public function test_farmer_cannot_update_other_users_harvest(): void
    {
        $owner = $this->createVerifiedFarmer();
        $other = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $harvest = Harvest::create([
            'user_id'               => $owner->id,
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'crop_category_id'      => $crop->crop_category_id,
            'crop_type'             => $crop->name,
            'variety'               => $variety->name,
            'quantity_kg'           => 500,
            'remaining_quantity_kg' => 500,
            'unit'                  => 'kg',
            'status'                => 'active',
            'destination_address'   => 'Test',
            'destination_latitude'  => 7.0,
            'destination_longitude' => 125.0,
        ]);

        $this->actingAs($other)->put("/harvests/{$harvest->id}", [
            'crop_id'         => $crop->id,
            'crop_variety_id' => $variety->id,
            'quantity_kg'     => 999,
        ])->assertStatus(403);
    }

    // ─── DESTROY ─────────────────────────────────────────────

    public function test_farmer_can_delete_own_harvest(): void
    {
        $user = $this->createVerifiedFarmer();
        ['crop' => $crop, 'variety' => $variety] = $this->createCropVariety();

        $harvest = Harvest::create([
            'user_id'               => $user->id,
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'crop_category_id'      => $crop->crop_category_id,
            'crop_type'             => $crop->name,
            'variety'               => $variety->name,
            'quantity_kg'           => 500,
            'remaining_quantity_kg' => 500,
            'unit'                  => 'kg',
            'status'                => 'active',
            'destination_address'   => 'Test',
            'destination_latitude'  => 7.0,
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
            'user_id'               => $owner->id,
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'crop_category_id'      => $crop->crop_category_id,
            'crop_type'             => $crop->name,
            'variety'               => $variety->name,
            'quantity_kg'           => 500,
            'remaining_quantity_kg' => 500,
            'unit'                  => 'kg',
            'status'                => 'active',
            'destination_address'   => 'Test',
            'destination_latitude'  => 7.0,
            'destination_longitude' => 125.0,
        ]);

        $this->actingAs($other)->delete("/harvests/{$harvest->id}")
            ->assertStatus(403);
    }
}
