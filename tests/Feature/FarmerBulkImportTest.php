<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\FarmerProfile;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FarmerBulkImportTest extends TestCase
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

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('farmers.csv', $content);
    }

    public function test_valid_csv_creates_farmers_approved_and_scoped_to_own_cooperative(): void
    {
        [$coop, $admin] = $this->coopAdmin();

        $content = "name,email,phone,farm_location,latitude,longitude\n"
            ."Juan Dela Cruz,juan@example.com,09171234567,Barangay Fatima,6.1164,125.1716\n"
            ."Maria Santos,maria@example.com,,,,\n";

        $response = $this->actingAs($admin)->post(route('coop.farmers.import.store'), [
            'file' => $this->csv($content),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'juan@example.com', 'role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $this->assertDatabaseHas('users', ['email' => 'maria@example.com', 'cooperative_id' => $coop->id]);

        $juan = User::where('email', 'juan@example.com')->first();
        $profile = FarmerProfile::where('user_id', $juan->id)->first();
        $this->assertEquals('approved', $profile->membership_status);
        $this->assertEquals($coop->id, $profile->cooperative_id);
        $this->assertEquals(6.1164, (float) $profile->latitude);
    }

    public function test_invalid_rows_are_skipped_and_reported_without_failing_valid_rows(): void
    {
        [, $admin] = $this->coopAdmin();
        User::factory()->create(['email' => 'existing@example.com']);

        $content = "name,email,phone,farm_location,latitude,longitude\n"
            .",missing-name@example.com,,,,\n" // missing name
            ."No Email,,,,,\n" // missing email
            ."Bad Email,not-an-email,,,,\n" // invalid email
            ."Already Registered,existing@example.com,,,,\n" // duplicate against existing user
            ."Dupe One,dupe@example.com,,,,\n"
            ."Dupe Two,dupe@example.com,,,,\n" // duplicate within file
            ."Out Of Range,badcoords@example.com,,,999,999\n" // invalid coordinates
            ."Valid Farmer,valid@example.com,,,,\n";

        $response = $this->actingAs($admin)->post(route('coop.farmers.import.store'), [
            'file' => $this->csv($content),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'valid@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'dupe@example.com']);
        $this->assertDatabaseCount('users', 4); // admin + existing + dupe one + valid
        $this->assertDatabaseMissing('users', ['email' => 'badcoords@example.com']);

        $response->assertSessionHas('importSkipped', function ($skipped) {
            return count($skipped) === 6;
        });
    }

    public function test_bulk_import_writes_one_summary_audit_log(): void
    {
        [, $admin] = $this->coopAdmin();

        $content = "name,email\nFarmer One,one@example.com\nFarmer Two,two@example.com\n";

        $this->actingAs($admin)->post(route('coop.farmers.import.store'), [
            'file' => $this->csv($content),
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'bulk_import_farmers']);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_non_coop_admin_cannot_import(): void
    {
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value]);

        $response = $this->actingAs($farmer)->post(route('coop.farmers.import.store'), [
            'file' => $this->csv("name,email\nA,a@example.com\n"),
        ]);

        $response->assertForbidden();
    }

    public function test_empty_file_does_not_crash(): void
    {
        [, $admin] = $this->coopAdmin();

        $response = $this->actingAs($admin)->post(route('coop.farmers.import.store'), [
            'file' => $this->csv(''),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
