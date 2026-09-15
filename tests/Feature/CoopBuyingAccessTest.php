<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoopBuyingAccessTest extends TestCase
{
    use RefreshDatabase;

    private function coop(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name' => 'GenSan AgCoop', 'business_permit_no' => 'BL-999',
            'phone' => '09171234567', 'is_verified' => true, 'logistics_type' => 'cooperative',
        ]);
        return $user;
    }

    private function independentLogistics(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name' => 'Linis Trucking', 'business_permit_no' => 'BL-111',
            'phone' => '09171234568', 'is_verified' => true, 'logistics_type' => 'company',
        ]);
        return $user;
    }

    public function test_coop_logistics_can_authcropboard_and_tracking(): void
    {
        $coop = $this->coop();

        $this->actingAs($coop)->get(route('buyer.crop-board'))->assertOk();
        $this->actingAs($coop)->get(route('buyer.tracking'))->assertOk();
    }

    public function test_independent_logistics_cannot_access_buyer_module(): void
    {
        $this->actingAs($this->independentLogistics())->get(route('buyer.crop-board'))->assertForbidden();
    }
}