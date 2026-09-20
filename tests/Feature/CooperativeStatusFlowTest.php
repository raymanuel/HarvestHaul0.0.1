<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CooperativeStatusFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeCooperative(string $status, array $overrides = []): Cooperative
    {
        $coopAdmin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value]);

        $cooperative = Cooperative::create(array_merge([
            'name' => "Coop {$status}",
            'type' => 'primary',
            'contact_number' => '09171234567',
            'official_email' => strtolower($status).'@example.com',
            'status' => $status,
            'coop_admin_user_id' => $coopAdmin->id,
        ], $overrides));

        $coopAdmin->update(['cooperative_id' => $cooperative->id]);

        return $cooperative;
    }

    /**
     * This is the case that previously could not even be inserted: sqlite's
     * CHECK constraint rejected 'requires_revision' because the enum-widening
     * migration only ran on mysql. Proves the migration is now portable.
     */
    public function test_admin_can_view_cooperative_in_every_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);

        foreach ([
            Cooperative::STATUS_PENDING,
            Cooperative::STATUS_UNDER_REVIEW,
            Cooperative::STATUS_REQUIRES_REVISION,
            Cooperative::STATUS_APPROVED,
            Cooperative::STATUS_REJECTED,
            Cooperative::STATUS_SUSPENDED,
        ] as $status) {
            $cooperative = $this->makeCooperative($status);

            $response = $this->actingAs($admin)->get(route('admin.cooperatives.show', $cooperative));

            $response->assertOk();
        }
    }

    public function test_coop_admin_sees_needs_revision_status_with_admin_notes(): void
    {
        $cooperative = $this->makeCooperative(Cooperative::STATUS_REQUIRES_REVISION, [
            'admin_notes' => 'Please upload a clearer CDA registration copy.',
        ]);

        $response = $this->actingAs($cooperative->coopAdminUser)->get(route('coop.status'));

        $response->assertOk();
        $response->assertSee('Needs your input');
        $response->assertSee('Please upload a clearer CDA registration copy.');
    }

    public function test_request_info_sets_requires_revision_not_pending(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);
        $cooperative = $this->makeCooperative(Cooperative::STATUS_PENDING);

        $response = $this->actingAs($admin)->post(
            route('admin.cooperatives.request-info', $cooperative),
            ['admin_notes' => 'Missing representative ID.']
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('cooperatives', [
            'id' => $cooperative->id,
            'status' => Cooperative::STATUS_REQUIRES_REVISION,
        ]);
    }

    public function test_reactivate_restores_suspended_cooperative_with_distinct_audit_action(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);
        $cooperative = $this->makeCooperative(Cooperative::STATUS_SUSPENDED);

        $response = $this->actingAs($admin)->post(route('admin.cooperatives.reactivate', $cooperative));

        $response->assertRedirect();
        $this->assertDatabaseHas('cooperatives', [
            'id' => $cooperative->id,
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'reactivated',
            'target_type' => 'cooperative',
            'target_id' => $cooperative->id,
        ]);
    }

    public function test_reactivate_rejects_a_cooperative_that_is_not_suspended(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);
        $cooperative = $this->makeCooperative(Cooperative::STATUS_APPROVED);

        $response = $this->actingAs($admin)->post(route('admin.cooperatives.reactivate', $cooperative));

        $response->assertStatus(422);
    }
}
