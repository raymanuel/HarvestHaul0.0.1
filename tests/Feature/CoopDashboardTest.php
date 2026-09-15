<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoopDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function coop(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name'       => 'GenSan AgCoop',
            'business_permit_no' => 'BL-999',
            'phone'              => '09171234567',
            'is_verified'        => true,
            'logistics_type'     => 'cooperative',
        ]);
        return $user;
    }

    public function test_coop_nav_links_customers_and_outbound(): void
    {
        $response = $this->actingAs($this->coop())->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee(route('coop.customers.index'));
        $response->assertSee(route('coop.outbound.index'));
    }

    public function test_independent_logistics_does_not_see_coop_nav(): void
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name'       => 'Linis Trucking',
            'business_permit_no' => 'BL-111',
            'phone'              => '09171234568',
            'is_verified'        => true,
            'logistics_type'     => 'company',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee(route('coop.customers.index'));
    }
}