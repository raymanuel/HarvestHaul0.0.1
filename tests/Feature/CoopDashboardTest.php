<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Cooperative;
use App\Models\FarmerProfile;
use App\Models\LogisticsProfile;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoopDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Approved cooperative record (the co-op admin's own).
     */
    private function approvedCoop(): Cooperative
    {
        return Cooperative::create([
            'name'                     => 'GenSan AgCoop',
            'type'                     => 'primary',
            'province'                 => 'South Cotabato',
            'city'                     => 'General Santos',
            'municipality'             => 'General Santos',
            'barangay'                 => 'SanIsidro',
            'contact_number'           => '09171234567',
            'official_email'           => 'coop@example.com',
            'status'                   => Cooperative::STATUS_APPROVED,
            'coop_admin_user_id'       => null,
            'registration_document_no' => 'CDA-2026-001',
        ]);
    }

    private function coop(): User
    {
        $cooperative = $this->approvedCoop();
        $user = User::factory()->create([
            'role'             => UserRole::COOP_ADMIN->value,
            'cooperative_id'   => $cooperative->id,
            'email_verified_at' => now(),
        ]);
        return $user;
    }

    public function test_coop_nav_links_farmers_and_trucks(): void
    {
        $response = $this->actingAs($this->coop())->get(route('coop.dashboard'));
        $response->assertOk();

        $response->assertSee(route('coop.farmers.index'));
        $response->assertSee(route('coop.trucks.index'));
    }

    public function test_independent_logistics_does_not_see_coop_nav(): void
    {
        $user = User::factory()->logisticsPartner()->create([
            'email_verified_at' => now(),
            'cooperative_id'   => null,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertForbidden();
    }

    public function test_farmer_approval_is_audited_and_notifies(): void
    {
        $coop = $this->coop();
        $cooperative = $coop->cooperative;

        $farmerUser = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $farmInfo = FarmerProfile::create([
            'user_id'            => $farmerUser->id,
            'phone'              => '09171234567',
            'farm_location'      => 'Polomolok Cornfield, GenSan',
            'affiliation_type'   => 'cooperative',
            'cooperative_id'     => $cooperative->id,
            'membership_status'  => 'pending',
        ]);

        $response = $this->actingAs($coop)->post(route('coop.farmers.approve', $farmerUser));
        $response->assertRedirect();

        $this->assertDatabaseHas('farmer_profiles', [
            'user_id'           => $farmerUser->id,
            'membership_status' => 'approved',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'approve_farmer_membership',
            'target_type' => 'farmer_profile',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id'  => $farmerUser->id,
            'category' => 'membership',
        ]);
    }
}
