<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CooperativeMembersTest extends TestCase
{
    use RefreshDatabase;

    private function createCooperative(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name'       => 'Test Cooperative',
            'business_permit_no' => 'BL-' . random_int(100000, 999999),
            'phone'              => '09123456789',
            'is_verified'        => true,
            'logistics_type'     => 'cooperative',
        ]);
        return $user;
    }

    private function createPendingMember(int $cooperativeId): User
    {
        $farmer = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $farmer->farmerProfile()->create([
            'phone'             => '09123456789',
            'farm_location'     => 'Test Farm',
            'is_verified'       => true,
            'latitude'          => 7.0,
            'longitude'         => 125.5,
            'affiliation_type'  => 'cooperative',
            'cooperative_id'    => $cooperativeId,
            'membership_status' => 'pending',
        ]);
        return $farmer;
    }

    public function test_cooperative_can_approve_pending_member(): void
    {
        $coop = $this->createCooperative();
        $farmer = $this->createPendingMember($coop->logisticsProfile->id);

        $this->actingAs($coop)
            ->post(route('logistics.members.approve', $farmer->farmerProfile))
            ->assertSessionHas('success');

        $this->assertEquals('approved', $farmer->farmerProfile->fresh()->membership_status);
    }
}
