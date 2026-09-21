<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\Crop;
use App\Models\FarmerProfile;
use App\Models\HaulRequest;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmerHaulRequestTest extends TestCase
{
    use RefreshDatabase;

    private function approvedFarmer(): User
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        FarmerProfile::factory()->for($farmer)->create([
            'affiliation_type' => 'cooperative', 'cooperative_id' => $coop->id, 'membership_status' => 'approved',
        ]);

        return $farmer;
    }

    public function test_store_persists_pickup_coordinates(): void
    {
        $farmer = $this->approvedFarmer();
        $crop = Crop::factory()->create();

        $response = $this->actingAs($farmer)->post(route('farmer.haul-requests.store'), [
            'crop_id' => $crop->id,
            'estimated_weight_kg' => 4000,
            'preferred_pickup_date' => now()->addDays(2)->toDateString(),
            'pickup_window_start' => '08:00',
            'pickup_window_end' => '12:00',
            'pickup_location' => 'Barangay Fatima, GenSan',
            'pickup_location_lat' => 6.1164,
            'pickup_location_lng' => 125.1716,
        ]);

        $response->assertRedirect();
        $request = HaulRequest::first();
        $this->assertNotNull($request);
        $this->assertEquals(6.1164, (float) $request->pickup_location_lat);
        $this->assertEquals(125.1716, (float) $request->pickup_location_lng);
    }

    public function test_store_requires_pickup_coordinates(): void
    {
        $farmer = $this->approvedFarmer();
        $crop = Crop::factory()->create();

        $response = $this->actingAs($farmer)->post(route('farmer.haul-requests.store'), [
            'crop_id' => $crop->id,
            'estimated_weight_kg' => 4000,
            'preferred_pickup_date' => now()->addDays(2)->toDateString(),
            'pickup_window_start' => '08:00',
            'pickup_window_end' => '12:00',
            'pickup_location' => 'Barangay Fatima, GenSan',
        ]);

        $response->assertSessionHasErrors(['pickup_location_lat', 'pickup_location_lng']);
        $this->assertDatabaseCount('haul_requests', 0);
    }
}
