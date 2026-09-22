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

    public function test_create_page_lists_cooperatives_nearest_first_with_member_count(): void
    {
        $farmer = $this->farmer();
        $farmer->farmerProfile->update(['latitude' => 7.0, 'longitude' => 125.0]);

        $near = Cooperative::factory()->approved()->create(['latitude' => 7.01, 'longitude' => 125.01]);
        $far = Cooperative::factory()->approved()->create(['latitude' => 8.5, 'longitude' => 126.5]);

        $member = $this->farmer();
        $member->farmerProfile->update([
            'cooperative_id' => $near->id,
            'membership_status' => 'approved',
            'affiliation_type' => 'cooperative',
        ]);

        $response = $this->actingAs($farmer)->get(route('farmer.join-cooperative.create'));

        $response->assertOk();
        $coops = $response->viewData('cooperatives');

        $this->assertSame($near->id, $coops->first()->id);
        $this->assertSame(1, $coops->firstWhere('id', $near->id)->member_farmers_count);
        $this->assertSame(0, $coops->firstWhere('id', $far->id)->member_farmers_count);
        $response->assertSee($near->name);
        $response->assertSee($far->name);
        $response->assertSee('Members');
        $response->assertSee('Distance');
    }

    public function test_create_page_shows_empty_state_when_no_cooperatives(): void
    {
        $farmer = $this->farmer();

        $response = $this->actingAs($farmer)->get(route('farmer.join-cooperative.create'));

        $response->assertOk();
        $response->assertSee('No cooperatives available yet');
    }
}
