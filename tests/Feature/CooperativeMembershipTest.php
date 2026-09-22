<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CooperativeMembershipTest extends TestCase
{
    use RefreshDatabase;

    private function farmer(): User
    {
        $user = User::factory()->farmer()->create(['email_verified_at' => now()]);
        FarmerProfile::factory()->verified()->for($user)->create();

        return $user;
    }

    private function coopAdmin(Cooperative $coop): User
    {
        $admin = $coop->coopAdminUser;
        $admin->update(['cooperative_id' => $coop->id]);

        return $admin;
    }

    public function test_requesting_to_join_does_not_grant_membership(): void
    {
        $farmer = $this->farmer();
        $coop = Cooperative::factory()->approved()->create();

        $this->actingAs($farmer)
            ->post(route('farmer.join-cooperative.store'), ['cooperative_id' => $coop->id])
            ->assertRedirect();

        $farmer->farmerProfile->refresh();

        $this->assertSame('pending', $farmer->farmerProfile->membership_status);
        $this->assertSame('independent', $farmer->farmerProfile->affiliation_type);
    }

    public function test_pending_farmer_is_not_treated_as_cooperative_member(): void
    {
        $farmer = $this->farmer();
        $coop = Cooperative::factory()->approved()->create();

        $this->actingAs($farmer)->post(route('farmer.join-cooperative.store'), ['cooperative_id' => $coop->id]);

        $this->assertFalse($farmer->farmerProfile->fresh()->isCooperativeMember());
    }

    public function test_approval_grants_cooperative_affiliation(): void
    {
        $farmer = $this->farmer();
        $coop = Cooperative::factory()->approved()->create();

        $this->actingAs($farmer)->post(route('farmer.join-cooperative.store'), ['cooperative_id' => $coop->id]);

        $this->actingAs($this->coopAdmin($coop))
            ->post(route('coop.farmers.approve', $farmer->id))
            ->assertRedirect();

        $farmer->farmerProfile->refresh();

        $this->assertSame('approved', $farmer->farmerProfile->membership_status);
        $this->assertSame('cooperative', $farmer->farmerProfile->affiliation_type);
        $this->assertTrue($farmer->farmerProfile->isCooperativeMember());
    }

    public function test_removed_member_is_no_longer_flagged_as_cooperative(): void
    {
        $farmer = $this->farmer();
        $coop = Cooperative::factory()->approved()->create();

        $this->actingAs($farmer)->post(route('farmer.join-cooperative.store'), ['cooperative_id' => $coop->id]);
        $this->actingAs($this->coopAdmin($coop))->post(route('coop.farmers.approve', $farmer->id));

        $this->actingAs($this->coopAdmin($coop))
            ->post(route('coop.farmers.remove', $farmer->id))
            ->assertRedirect();

        $farmer->farmerProfile->refresh();

        $this->assertSame('removed', $farmer->farmerProfile->membership_status);
        $this->assertSame('independent', $farmer->farmerProfile->affiliation_type);
        $this->assertFalse($farmer->farmerProfile->isCooperativeMember());
    }
}
