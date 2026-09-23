<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use App\Models\UserRole;
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

    public function test_coop_admin_can_view_settings_page(): void
    {
        [$admin] = $this->coopAdmin();

        $this->actingAs($admin)->get(route('coop.settings.edit'))->assertOk();
    }

    public function test_coop_admin_can_set_a_custom_cluster_radius(): void
    {
        [$admin, $coop] = $this->coopAdmin();

        $this->actingAs($admin)
            ->put(route('coop.settings.update'), ['max_cluster_radius_km' => 35])
            ->assertRedirect(route('coop.settings.edit'));

        $this->assertSame(35.0, $coop->fresh()->max_cluster_radius_km);
    }

    public function test_coop_admin_can_clear_the_custom_radius_back_to_platform_default(): void
    {
        [$admin, $coop] = $this->coopAdmin();
        $coop->update(['max_cluster_radius_km' => 35]);

        $this->actingAs($admin)
            ->put(route('coop.settings.update'), ['max_cluster_radius_km' => ''])
            ->assertRedirect(route('coop.settings.edit'));

        $this->assertNull($coop->fresh()->max_cluster_radius_km);
    }

    public function test_negative_radius_is_rejected(): void
    {
        [$admin] = $this->coopAdmin();

        $this->actingAs($admin)
            ->put(route('coop.settings.update'), ['max_cluster_radius_km' => -5])
            ->assertSessionHasErrors('max_cluster_radius_km');
    }

    public function test_farmer_cannot_access_coop_settings(): void
    {
        $farmer = User::factory()->farmer()->create(['email_verified_at' => now()]);

        $this->actingAs($farmer)->get(route('coop.settings.edit'))->assertForbidden();
    }
}
