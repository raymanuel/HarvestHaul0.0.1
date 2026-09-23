<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldStaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private function coopAdmin(): array
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);

        return [$coop, $admin];
    }

    public function test_coop_admin_can_view_the_staff_index(): void
    {
        [, $admin] = $this->coopAdmin();

        $response = $this->actingAs($admin)->get(route('coop.staff.index'));

        $response->assertOk();
    }

    public function test_coop_admin_can_create_a_field_receiving_account_usable_immediately(): void
    {
        [$coop, $admin] = $this->coopAdmin();

        $response = $this->actingAs($admin)->post(route('coop.staff.store'), [
            'name' => 'Ana Cruz',
            'email' => 'ana@example.com',
            'password' => 'Password1!',
            'phone' => '09171234567',
        ]);

        $response->assertRedirect(route('coop.staff.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'ana@example.com',
            'role' => UserRole::FIELD_RECEIVING->value,
            'cooperative_id' => $coop->id,
            'status' => 'active',
        ]);

        $staff = User::where('email', 'ana@example.com')->first();
        $this->assertNotNull($staff->email_verified_at);

        // Usable immediately — can log in and reach the field receiving queue.
        $response = $this->actingAs($staff)->get(route('field.receiving.index'));
        $response->assertOk();
    }

    public function test_duplicate_email_is_rejected(): void
    {
        [, $admin] = $this->coopAdmin();
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($admin)->post(route('coop.staff.store'), [
            'name' => 'Ana Cruz',
            'email' => 'taken@example.com',
            'password' => 'Password1!',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_a_farmer_cannot_access_staff_management(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);

        $response = $this->actingAs($farmer)->get(route('coop.staff.index'));

        $response->assertForbidden();
    }

    public function test_staff_index_only_shows_own_cooperatives_field_staff(): void
    {
        [$coop, $admin] = $this->coopAdmin();
        $ownStaff = User::factory()->create(['role' => UserRole::FIELD_RECEIVING->value, 'cooperative_id' => $coop->id, 'name' => 'Own Staff']);

        $otherCoop = Cooperative::create([
            'name' => 'Other Coop', 'type' => 'primary',
            'contact_number' => '09170000000', 'official_email' => 'other@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        User::factory()->create(['role' => UserRole::FIELD_RECEIVING->value, 'cooperative_id' => $otherCoop->id, 'name' => 'Other Coop Staff']);

        $response = $this->actingAs($admin)->get(route('coop.staff.index'));

        $response->assertOk();
        $response->assertSee('Own Staff');
        $response->assertDontSee('Other Coop Staff');
    }
}
