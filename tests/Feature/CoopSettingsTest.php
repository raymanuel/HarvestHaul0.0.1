<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoopSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function coopAdmin(): array
    {
        $coop = Cooperative::factory()->approved()->create();
        $admin = $coop->coopAdminUser;
        $admin->update(['cooperative_id' => $coop->id]);

        return [$admin, $coop];
    }

    public function test_pickup_planner_shows_the_cooperatives_current_radius(): void
    {
        [$admin, $coop] = $this->coopAdmin();
        $coop->update(['max_cluster_radius_km' => 35]);

        $this->actingAs($admin)
            ->get(route('coop.pickups.create'))
            ->assertOk()
            ->assertSee('value="35"', false);
    }

    public function test_coop_admin_can_set_a_custom_cluster_radius_from_the_planner(): void
    {
        [$admin, $coop] = $this->coopAdmin();
        $plannerUrl = route('coop.pickups.create', ['date' => today()->addDay()->toDateString()]);

        $this->actingAs($admin)
            ->from($plannerUrl)
            ->put(route('coop.settings.update'), ['max_cluster_radius_km' => 35])
            ->assertRedirect($plannerUrl);

        $this->assertSame(35.0, $coop->fresh()->max_cluster_radius_km);
    }

    public function test_coop_admin_can_clear_the_custom_radius_back_to_platform_default(): void
    {
        [$admin, $coop] = $this->coopAdmin();
        $coop->update(['max_cluster_radius_km' => 35]);
        $plannerUrl = route('coop.pickups.create');

        $this->actingAs($admin)
            ->from($plannerUrl)
            ->put(route('coop.settings.update'), ['max_cluster_radius_km' => ''])
            ->assertRedirect($plannerUrl);

        $this->assertNull($coop->fresh()->max_cluster_radius_km);
    }

    public function test_negative_radius_is_rejected(): void
    {
        [$admin] = $this->coopAdmin();

        $this->actingAs($admin)
            ->put(route('coop.settings.update'), ['max_cluster_radius_km' => -5])
            ->assertSessionHasErrors('max_cluster_radius_km');
    }

    public function test_farmer_cannot_update_coop_settings(): void
    {
        $farmer = User::factory()->farmer()->create(['email_verified_at' => now()]);

        $this->actingAs($farmer)
            ->put(route('coop.settings.update'), ['max_cluster_radius_km' => 35])
            ->assertForbidden();
    }
}
