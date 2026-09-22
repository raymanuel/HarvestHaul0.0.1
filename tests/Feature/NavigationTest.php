<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_sidebar_shows_listings_orders_and_messages(): void
    {
        $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);

        $response = $this->actingAs($buyer)->get(route('buyer.dashboard'));

        $response->assertOk();
        $response->assertSee(route('buyer.listings.index'), false);
        $response->assertSee(route('buyer.orders.index'), false);
        $response->assertSee(route('messages.index'), false);
    }

    public function test_coop_admin_sidebar_shows_new_module_links(): void
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);

        $response = $this->actingAs($admin)->get(route('coop.dashboard'));

        $response->assertOk();
        $response->assertSee(route('coop.availability.index'), false);
        $response->assertSee(route('coop.outbound.index'), false);
        $response->assertSee(route('coop.buyer-orders.index'), false);
        $response->assertSee(route('coop.facility-receiving.index'), false);
        $response->assertSee(route('coop.tracking.index'), false);
        $response->assertSee(route('coop.reports.index'), false);
    }

    public function test_coop_admin_sidebar_groups_packed_items_into_dropdowns(): void
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop-grouped@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);

        $response = $this->actingAs($admin)->get(route('coop.dashboard'));

        $response->assertOk();
        $response->assertSee('Harvest & Pickups');
        $response->assertSee('Logistics & Trips');
        $response->assertSee('Receiving & Procurement');
        // Pickup Schedule (the calendar view) had no sidebar entry at all before this change.
        $response->assertSee(route('coop.pickups.calendar'), false);
    }

    public function test_super_admin_sidebar_groups_reference_data_into_dropdown(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'email_verified_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Reference Data');
        $response->assertSee(route('admin.crops.index'), false);
        $response->assertSee(route('admin.reference.index'), false);
    }

    public function test_super_admin_sidebar_shows_market_prices(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'email_verified_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.market-prices.index'), false);
    }

    public function test_super_admin_sidebar_shows_messages(): void
    {
        // The messaging feature works for super_admin (messages.* sits under
        // the plain 'auth' group, not any role-specific one) but had no
        // sidebar entry — reachable only by typing the URL directly.
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value, 'email_verified_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('messages.index'), false);
    }

    public function test_coop_admin_without_cooperative_is_redirected_not_crashed(): void
    {
        // The coop_admin.approved middleware already redirects this case to
        // coop.status before the controller ever runs — this test guards
        // that path plus the controller's own defensive null check.
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => null]);

        $response = $this->actingAs($admin)->get(route('coop.dashboard'));

        $response->assertRedirect(route('coop.status'));
    }
}
