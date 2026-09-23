<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Cooperative\ConsolidationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverManagementTest extends TestCase
{
    use RefreshDatabase;

    private function coopAdmin(): User
    {
        $coop = Cooperative::factory()->approved()->create();
        $admin = $coop->coopAdminUser;
        $admin->update(['cooperative_id' => $coop->id]);

        return $admin;
    }

    public function test_coop_admin_can_create_a_driver(): void
    {
        $admin = $this->coopAdmin();

        $this->actingAs($admin)
            ->post(route('coop.drivers.store'), [
                'name' => 'Juan Dela Cruz',
                'email' => 'juan.driver@example.com',
                'password' => 'password123',
                'phone' => '09171234567',
                'license_no' => 'DL-12345678',
            ])
            ->assertRedirect(route('coop.drivers.index'));

        $driver = User::where('email', 'juan.driver@example.com')->first();

        $this->assertNotNull($driver);
        $this->assertSame(UserRole::DELIVERY_PERSONNEL->value, $driver->role);
        $this->assertSame($admin->cooperative_id, $driver->cooperative_id);
        $this->assertSame('active', $driver->status);
        $this->assertNotNull($driver->driverProfile);
        $this->assertSame('active', $driver->driverProfile->employment_status);
    }

    public function test_created_driver_appears_in_available_drivers_pool(): void
    {
        $admin = $this->coopAdmin();
        $coop = $admin->cooperative;

        $this->actingAs($admin)->post(route('coop.drivers.store'), [
            'name' => 'Maria Santos',
            'email' => 'maria.driver@example.com',
            'password' => 'password123',
            'license_no' => 'DL-87654321',
        ]);

        $available = app(ConsolidationEngine::class)->availableDrivers($coop, now()->toDateString());

        $this->assertTrue($available->pluck('name')->contains('Maria Santos'));
    }

    public function test_coop_admin_without_a_cooperative_is_redirected_to_status(): void
    {
        // EnsureUserIsApprovedCoopAdmin middleware redirects before the
        // controller's own guard ever runs — this locks in that behavior.
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => null, 'email_verified_at' => now()]);

        $this->actingAs($admin)->get(route('coop.drivers.index'))->assertRedirect(route('coop.status'));
        $this->actingAs($admin)->get(route('coop.drivers.create'))->assertRedirect(route('coop.status'));
    }

    public function test_farmer_cannot_access_driver_management(): void
    {
        $farmer = User::factory()->farmer()->create(['email_verified_at' => now()]);

        $this->actingAs($farmer)->get(route('coop.drivers.index'))->assertForbidden();
    }

    public function test_index_page_renders_with_empty_state_then_lists_created_driver(): void
    {
        $admin = $this->coopAdmin();

        $empty = $this->actingAs($admin)->get(route('coop.drivers.index'));
        $empty->assertOk();
        $empty->assertSee('No drivers yet');

        $this->actingAs($admin)->post(route('coop.drivers.store'), [
            'name' => 'Pedro Reyes',
            'email' => 'pedro.driver@example.com',
            'password' => 'password123',
            'license_no' => 'DL-11112222',
        ]);

        $listed = $this->actingAs($admin)->get(route('coop.drivers.index'));
        $listed->assertOk();
        $listed->assertSee('Pedro Reyes');
    }

    public function test_create_page_renders(): void
    {
        $admin = $this->coopAdmin();

        $this->actingAs($admin)->get(route('coop.drivers.create'))->assertOk();
    }

    public function test_created_driver_can_actually_log_in_and_reach_their_dashboard(): void
    {
        // email_verified_at is not in User::$fillable, so passing it inside
        // User::create()'s array silently no-ops (mass-assignment drops it).
        // Every delivery_personnel route sits behind the 'verified'
        // middleware group (routes/web.php:155) — a driver created without
        // this set can log in but gets bounced to the email-verification
        // notice page and can reach nothing.
        $admin = $this->coopAdmin();

        $this->actingAs($admin)->post(route('coop.drivers.store'), [
            'name' => 'Ricardo Cruz',
            'email' => 'ricardo.driver@example.com',
            'password' => 'password123',
            'license_no' => 'DL-99998888',
        ]);

        $driver = User::where('email', 'ricardo.driver@example.com')->first();

        $this->assertTrue($driver->hasVerifiedEmail());
        $this->actingAs($driver)->get(route('delivery.dashboard'))->assertOk();
    }
}
