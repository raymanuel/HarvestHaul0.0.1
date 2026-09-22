<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_edit_a_users_details_from_the_page(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $user = User::factory()->create(['role' => UserRole::FARMER->value, 'name' => 'Old Name']);

        $response = $this->actingAs($superAdmin)->put(route('admin.users.update', $user), [
            'name' => 'New Name',
            'email' => $user->email,
            'phone' => '09171234567',
            'cooperative_id' => $coop->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'cooperative_id' => $coop->id]);
    }

    public function test_the_edit_form_is_reachable_from_the_users_index_page(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);
        $user = User::factory()->create(['role' => UserRole::FARMER->value]);

        $response = $this->actingAs($superAdmin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee(route('admin.users.update', $user), false);
    }
}
