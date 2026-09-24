<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropGrade;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\ReceivingRecord;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceivingTest extends TestCase
{
    use RefreshDatabase;

    private function cooperative(): Cooperative
    {
        return Cooperative::create([
            'name' => 'GenSan AgCoop', 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => 'coop@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
    }

    private function pickedUpStop(Cooperative $coop): array
    {
        $fieldUser = User::factory()->create(['role' => UserRole::FIELD_RECEIVING->value, 'cooperative_id' => $coop->id, 'email_verified_at' => now()]);
        $driver = User::factory()->create(['role' => UserRole::DELIVERY_PERSONNEL->value, 'cooperative_id' => $coop->id]);
        $truck = Truck::factory()->create(['cooperative_id' => $coop->id]);

        $req = HaulRequest::factory()->create([
            'cooperative_id' => $coop->id,
            'status' => HaulRequest::STATUS_SCHEDULED,
            'estimated_sacks' => 80,
            'estimated_weight_kg' => 4000,
        ]);

        $job = HaulJob::create([
            'haul_request_id' => null, 'cooperative_id' => $coop->id,
            'delivery_personnel_id' => $driver->id, 'truck_id' => $truck->id,
            'pickup_date' => today(), 'status' => HaulJob::STATUS_PICKED_UP,
        ]);

        $stop = HaulJobStop::create([
            'haul_job_id' => $job->id, 'haul_request_id' => $req->id,
            'sequence_no' => 1, 'status' => HaulJobStop::STATUS_PICKED_UP,
        ]);

        $grade = CropGrade::factory()->create();

        return [$fieldUser, $job, $stop, $grade];
    }

    public function test_receiving_views_render(): void
    {
        $coop = $this->cooperative();
        [$fieldUser, $job, $stop, $grade] = $this->pickedUpStop($coop);

        $this->actingAs($fieldUser)->get(route('field.receiving.index'))->assertOk();
        $this->actingAs($fieldUser)->get(route('field.receiving.show', $job))->assertOk();
        $this->actingAs($fieldUser)->get(route('field.receiving.create', [$job, $stop]))->assertOk();
    }

    public function test_store_requires_a_grade(): void
    {
        $coop = $this->cooperative();
        [$fieldUser, $job, $stop, $grade] = $this->pickedUpStop($coop);

        $response = $this->actingAs($fieldUser)->post(route('field.receiving.store', [$job, $stop]), [
            'actual_sacks' => 78,
            'actual_weight_kg' => 3920,
        ]);

        $response->assertSessionHasErrors('crop_grade_id');
        $this->assertDatabaseCount('receiving_records', 0);
    }

    public function test_store_without_price_leaves_amounts_null_and_status_pending(): void
    {
        $coop = $this->cooperative();
        [$fieldUser, $job, $stop, $grade] = $this->pickedUpStop($coop);

        $response = $this->actingAs($fieldUser)->post(route('field.receiving.store', [$job, $stop]), [
            'crop_grade_id' => $grade->id,
            'actual_sacks' => 78,
            'actual_weight_kg' => 3920,
        ]);

        $response->assertRedirect();

        $record = ReceivingRecord::first();
        $this->assertNotNull($record);
        $this->assertEquals($stop->haul_request_id, $record->haul_request_id);
        $this->assertNull($record->buying_price_per_kg);
        $this->assertNull($record->total_amount);
        $this->assertEquals(ReceivingRecord::STATUS_PENDING, $record->status);

        // Actual weight is authoritative — differs from the farmer's estimate (spec 11.5).
        $this->assertEquals(3920.00, (float) $record->actual_weight_kg);

        // Crop availability isn't created until the cooperative confirms the
        // procurement (Module 14) — not at raw receiving time.
        $this->assertDatabaseCount('crop_availabilities', 0);
    }

    public function test_store_with_price_awaits_coop_confirmation(): void
    {
        $coop = $this->cooperative();
        [$fieldUser, $job, $stop, $grade] = $this->pickedUpStop($coop);

        $response = $this->actingAs($fieldUser)->post(route('field.receiving.store', [$job, $stop]), [
            'crop_grade_id' => $grade->id,
            'actual_sacks' => 78,
            'actual_weight_kg' => 3920,
            'buying_price_per_kg' => 18,
        ]);

        $response->assertRedirect();

        $record = ReceivingRecord::first();
        $this->assertEquals(70560.00, (float) $record->total_amount);
        // Field receiving staff can price it, but only a coop admin can
        // confirm (in the procurement queue) — status waits at "priced".
        $this->assertEquals(ReceivingRecord::STATUS_PRICED, $record->status);
        $this->assertNull($record->confirmed_by);
    }

    public function test_duplicate_receiving_for_same_stop_rejected(): void
    {
        $coop = $this->cooperative();
        [$fieldUser, $job, $stop, $grade] = $this->pickedUpStop($coop);

        $payload = ['crop_grade_id' => $grade->id, 'actual_sacks' => 78, 'actual_weight_kg' => 3920];
        $this->actingAs($fieldUser)->post(route('field.receiving.store', [$job, $stop]), $payload);

        $response = $this->actingAs($fieldUser)->post(route('field.receiving.store', [$job, $stop]), $payload);

        $response->assertSessionHasErrors('stop');
        $this->assertDatabaseCount('receiving_records', 1);
    }

    public function test_store_rejected_before_stop_is_picked_up(): void
    {
        $coop = $this->cooperative();
        [$fieldUser, $job, $stop, $grade] = $this->pickedUpStop($coop);
        $stop->update(['status' => HaulJobStop::STATUS_PENDING]);

        $response = $this->actingAs($fieldUser)->post(route('field.receiving.store', [$job, $stop]), [
            'crop_grade_id' => $grade->id,
            'actual_sacks' => 78,
            'actual_weight_kg' => 3920,
        ]);

        $response->assertSessionHasErrors('stop');
    }

    /**
     * The create form previously had no $errors display for any field — a
     * rejected submission (missing grade, duplicate record, etc.) silently
     * reloaded the same page with nothing visible ("the button doesn't work").
     */
    public function test_rejected_submission_shows_a_visible_error_on_reload(): void
    {
        $coop = $this->cooperative();
        [$fieldUser, $job, $stop, $grade] = $this->pickedUpStop($coop);

        $response = $this->actingAs($fieldUser)
            ->from(route('field.receiving.create', [$job, $stop]))
            ->followingRedirects()
            ->post(route('field.receiving.store', [$job, $stop]), [
                'actual_sacks' => 78,
                'actual_weight_kg' => 3920,
            ]);

        $response->assertOk();
        $response->assertSee("Couldn't record receiving", false);
    }
}
