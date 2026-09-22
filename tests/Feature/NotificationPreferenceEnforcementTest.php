<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\FarmerProfile;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_created_by_default_when_no_preference_row_exists(): void
    {
        $user = User::factory()->create();

        Notification::create([
            'user_id' => $user->id, 'title' => 'Test', 'message' => 'Test', 'category' => 'haul',
        ]);

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'category' => 'haul']);
    }

    public function test_notification_blocked_when_category_disabled(): void
    {
        $user = User::factory()->create();
        NotificationPreference::create(['user_id' => $user->id, 'category' => 'haul', 'enabled' => false]);

        Notification::create([
            'user_id' => $user->id, 'title' => 'Test', 'message' => 'Test', 'category' => 'haul',
        ]);

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_notification_allowed_when_category_explicitly_enabled(): void
    {
        $user = User::factory()->create();
        NotificationPreference::create(['user_id' => $user->id, 'category' => 'haul', 'enabled' => true]);

        Notification::create([
            'user_id' => $user->id, 'title' => 'Test', 'message' => 'Test', 'category' => 'haul',
        ]);

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'category' => 'haul']);
    }

    public function test_disabling_one_category_does_not_block_another(): void
    {
        $user = User::factory()->create();
        NotificationPreference::create(['user_id' => $user->id, 'category' => 'haul', 'enabled' => false]);

        Notification::create([
            'user_id' => $user->id, 'title' => 'Test', 'message' => 'Test', 'category' => 'message',
        ]);

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'category' => 'message']);
    }

    public function test_membership_approval_respects_disabled_preference_end_to_end(): void
    {
        $coop = Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        FarmerProfile::factory()->for($farmer)->create([
            'affiliation_type' => 'cooperative', 'cooperative_id' => $coop->id, 'membership_status' => 'pending',
        ]);
        NotificationPreference::create(['user_id' => $farmer->id, 'category' => 'membership', 'enabled' => false]);

        $this->actingAs($admin)->post(route('coop.farmers.approve', $farmer));

        $this->assertDatabaseMissing('notifications', ['user_id' => $farmer->id, 'category' => 'membership']);
    }
}
