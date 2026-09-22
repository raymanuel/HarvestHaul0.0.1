<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileAccessScopeTest extends TestCase
{
    use RefreshDatabase;

    private function coopWithRepIdDocument(): Cooperative
    {
        Storage::fake('local');
        $path = Storage::disk('local')->putFile('coop-documents', UploadedFile::fake()->create('rep-id.pdf', 10));

        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
            'rep_id_document_path' => $path,
        ]);
    }

    public function test_a_farmer_in_the_cooperative_cannot_view_the_reps_kyc_document(): void
    {
        $coop = $this->coopWithRepIdDocument();
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);

        $response = $this->actingAs($farmer)->get(route('files.show', ['type' => 'coop-document', 'id' => $coop->id, 'slot' => 'rep_id']));

        $response->assertForbidden();
    }

    public function test_delivery_personnel_in_the_cooperative_cannot_view_the_reps_kyc_document(): void
    {
        $coop = $this->coopWithRepIdDocument();
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);

        $response = $this->actingAs($driver)->get(route('files.show', ['type' => 'coop-document', 'id' => $coop->id, 'slot' => 'rep_id']));

        $response->assertForbidden();
    }

    public function test_the_coop_admin_of_that_cooperative_can_view_it(): void
    {
        $coop = $this->coopWithRepIdDocument();
        $admin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $coop->id]);

        $response = $this->actingAs($admin)->get(route('files.show', ['type' => 'coop-document', 'id' => $coop->id, 'slot' => 'rep_id']));

        $response->assertOk();
    }

    public function test_super_admin_can_view_it(): void
    {
        $coop = $this->coopWithRepIdDocument();
        $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN->value]);

        $response = $this->actingAs($superAdmin)->get(route('files.show', ['type' => 'coop-document', 'id' => $coop->id, 'slot' => 'rep_id']));

        $response->assertOk();
    }

    public function test_a_coop_admin_of_a_different_cooperative_cannot_view_it(): void
    {
        $coop = $this->coopWithRepIdDocument();
        $otherCoop = Cooperative::create([
            'name' => 'Other Coop', 'type' => 'primary',
            'contact_number' => '09170000000', 'official_email' => 'other@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $otherAdmin = User::factory()->create(['role' => UserRole::COOP_ADMIN->value, 'cooperative_id' => $otherCoop->id]);

        $response = $this->actingAs($otherAdmin)->get(route('files.show', ['type' => 'coop-document', 'id' => $coop->id, 'slot' => 'rep_id']));

        $response->assertForbidden();
    }
}
