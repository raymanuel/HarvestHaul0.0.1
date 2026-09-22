<?php

namespace Tests\Feature;

use App\Models\Cooperative;
use App\Models\Crop;
use App\Models\CropGrade;
use App\Models\HaulJob;
use App\Models\HaulJobStop;
use App\Models\HaulRequest;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldReceivingIdorTest extends TestCase
{
    use RefreshDatabase;

    private function coopWithJobAndStop(string $suffix): array
    {
        $coop = Cooperative::create([
            'name' => "Coop {$suffix}", 'type' => 'primary',
            'contact_number' => '09171234567', 'official_email' => strtolower($suffix).'@example.com',
            'status' => Cooperative::STATUS_APPROVED,
        ]);
        $farmer = User::factory()->create(['role' => UserRole::FARMER->value, 'cooperative_id' => $coop->id]);
        $crop = Crop::factory()->create();

        $haulRequest = HaulRequest::create([
            'farmer_id' => $farmer->id,
            'cooperative_id' => $coop->id,
            'crop_id' => $crop->id,
            'estimated_weight_kg' => 500,
            'status' => HaulRequest::STATUS_SCHEDULED,
            'preferred_pickup_date' => now()->addDay(),
            'pickup_window_start' => '08:00',
            'pickup_window_end' => '10:00',
            'pickup_location' => 'Farm',
            'pickup_location_lat' => 6.1,
            'pickup_location_lng' => 125.1,
        ]);

        $haulJob = HaulJob::create([
            'cooperative_id' => $coop->id,
            'haul_request_id' => $haulRequest->id,
            'job_type' => 'pickup',
            'status' => HaulJob::STATUS_PICKED_UP,
            'pickup_date' => now(),
        ]);

        $stop = HaulJobStop::create([
            'haul_job_id' => $haulJob->id,
            'haul_request_id' => $haulRequest->id,
            'sequence_no' => 1,
            'status' => HaulJobStop::STATUS_PICKED_UP,
        ]);

        return [$coop, $haulJob, $stop, $farmer];
    }

    public function test_field_user_cannot_view_receiving_form_for_a_stop_from_another_cooperative(): void
    {
        [$coopA, $jobA] = $this->coopWithJobAndStop('A');
        [$coopB, $jobB, $stopB] = $this->coopWithJobAndStop('B');

        $fieldUserA = User::factory()->create(['role' => UserRole::FIELD_RECEIVING->value, 'cooperative_id' => $coopA->id]);

        // fieldUserA's own job (jobA), but a stop ID belonging to coop B.
        $response = $this->actingAs($fieldUserA)->get(route('field.receiving.create', ['haulJob' => $jobA->id, 'stop' => $stopB->id]));

        $response->assertNotFound();
    }

    public function test_field_user_cannot_submit_receiving_for_a_stop_from_another_cooperative(): void
    {
        [$coopA, $jobA] = $this->coopWithJobAndStop('A');
        [$coopB, $jobB, $stopB] = $this->coopWithJobAndStop('B');

        $fieldUserA = User::factory()->create(['role' => UserRole::FIELD_RECEIVING->value, 'cooperative_id' => $coopA->id]);
        $grade = CropGrade::factory()->create();

        $response = $this->actingAs($fieldUserA)->post(route('field.receiving.store', ['haulJob' => $jobA->id, 'stop' => $stopB->id]), [
            'crop_grade_id' => $grade->id,
            'actual_sacks' => 10,
            'actual_weight_kg' => 500,
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('receiving_records', ['cooperative_id' => $coopA->id]);
    }

    public function test_field_user_can_still_receive_a_stop_that_actually_belongs_to_their_own_job(): void
    {
        [$coopA, $jobA, $stopA] = $this->coopWithJobAndStop('A');

        $fieldUserA = User::factory()->create(['role' => UserRole::FIELD_RECEIVING->value, 'cooperative_id' => $coopA->id]);
        $grade = CropGrade::factory()->create();

        $response = $this->actingAs($fieldUserA)->get(route('field.receiving.create', ['haulJob' => $jobA->id, 'stop' => $stopA->id]));
        $response->assertOk();

        $response = $this->actingAs($fieldUserA)->post(route('field.receiving.store', ['haulJob' => $jobA->id, 'stop' => $stopA->id]), [
            'crop_grade_id' => $grade->id,
            'actual_sacks' => 10,
            'actual_weight_kg' => 500,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('receiving_records', ['cooperative_id' => $coopA->id, 'haul_job_id' => $jobA->id]);
    }
}
