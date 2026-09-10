<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use App\Models\Truck;
use App\Models\User;
use App\Notifications\InvoiceReady;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InvoiceReadyLinkAndRolesTest extends TestCase
{
    use RefreshDatabase;

    private function createLogisticsUser(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name'       => 'Test Logistics',
            'business_permit_no' => 'BL-12345',
            'phone'              => '09123456789',
            'is_verified'        => true,
            'logistics_type'     => 'company',
        ]);
        return $user;
    }

    private function createFarmerUser(): User
    {
        $user = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $user->farmerProfile()->create([
            'phone'            => '09123456789',
            'farm_location'    => 'Test Farm',
            'is_verified'      => true,
            'latitude'         => 7.0,
            'longitude'        => 125.5,
            'affiliation_type' => 'independent',
        ]);
        return $user;
    }

    private function createTruck(User $logisticsUser): Truck
    {
        return Truck::create([
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_name'           => 'Test Truck',
            'plate_number'         => 'ABC-1234',
            'capacity_kg'          => 5000,
            'status'               => 'available',
            'vehicle_type'         => 'truck',
        ]);
    }

    private function createJob(User $logisticsUser): PoolingJob
    {
        $truck = $this->createTruck($logisticsUser);

        return PoolingJob::create([
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'truck_id'             => $truck->id,
            'status'               => PoolingJobStatus::PENDING,
            'total_kg'             => 500,
            'truck_capacity_kg'    => 5000,
            'farm_count'           => 1,
        ]);
    }

    private function createInvoice(PoolingJob $job, User $logisticsUser): Invoice
    {
        return Invoice::create([
            'pooling_job_id'       => $job->id,
            'logistics_profile_id' => $logisticsUser->logisticsProfile->id,
            'invoice_number'       => 'INV-20260910-001',
            'total_amount'         => 1500.00,
            'total_kg'             => 300.00,
            'farm_count'           => 1,
            'status'               => 'sent',
            'generated_at'         => now(),
            'sent_at'              => now(),
        ]);
    }

    // ─── INVOICE LINK ─────────────────────────────────────────

    public function test_invoice_notification_link_uses_numeric_id(): void
    {
        Notification::fake();

        $logisticsUser = $this->createLogisticsUser();
        $job = $this->createJob($logisticsUser);
        $invoice = $this->createInvoice($job, $logisticsUser);

        $notification = new InvoiceReady($invoice);
        $toArray = $notification->toArray($logisticsUser);

        // The link must be a numeric route, not the string invoice_number
        $this->assertMatchesRegularExpression('#/invoices/\d+/download#', $toArray['link'],
            'Notification link must use the numeric invoice id');
        $this->assertStringNotContainsString($invoice->invoice_number, $toArray['link'],
            'Notification link must not contain the string invoice_number');
        $this->assertEquals(route('invoices.download', $invoice->id), $toArray['link']);
    }

    public function test_invoice_mail_action_uses_numeric_id(): void
    {
        Notification::fake();

        $logisticsUser = $this->createLogisticsUser();
        $job = $this->createJob($logisticsUser);
        $invoice = $this->createInvoice($job, $logisticsUser);

        $notification = new InvoiceReady($invoice);
        $mail = $notification->toMail($logisticsUser);

        // The action URL must be numeric
        $actionUrl = $mail->actionUrl;
        $this->assertMatchesRegularExpression('#/invoices/\d+/download#', $actionUrl,
            'Mail action URL must use the numeric invoice id');
        $this->assertStringNotContainsString($invoice->invoice_number, $actionUrl,
            'Mail action URL must not contain the string invoice_number');
    }

    // ─── LOGISTICS CANNOT ACCEPT/REJECT ───────────────────────

    public function test_logistics_cannot_accept_proposal(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmerF = $this->createFarmerUser();
        $job = $this->createJob($logisticsUser);

        // Attach a harvest to the job
        $category = \App\Models\CropCategory::firstOrCreate(['name' => 'Grains'], ['status' => 'active']);
        $crop = \App\Models\Crop::firstOrCreate(['name' => 'Rice'], ['crop_category_id' => $category->id, 'status' => 'active']);
        $variety = \App\Models\CropVariety::firstOrCreate(['crop_id' => $crop->id, 'name' => 'Standard'], ['status' => 'active']);

        $harvest = \App\Models\Harvest::create([
            'user_id'               => $farmerF->id,
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'crop_category_id'      => $category->id,
            'crop_type'             => $crop->name,
            'variety'               => $variety->name,
            'quantity_kg'           => 100,
            'remaining_quantity_kg' => 100,
            'unit'                  => 'kg',
            'status'                => 'active',
            'destination_address'   => 'Test',
            'destination_latitude'  => 7.0,
            'destination_longitude' => 125.0,
        ]);

        $job->harvests()->attach($harvest->id, [
            'pickup_order' => 1,
            'quantity_kg'  => 100,
            'status'       => 'pending',
            'cost_share'   => 500,
        ]);

        $response = $this->actingAs($logisticsUser)
            ->post(route('pooling.accept', $job->id));

        $response->assertStatus(403);

        // Pivot unchanged
        $job->refresh();
        $this->assertEquals(PoolingJobStatus::PENDING, $job->status);
        $job->load('harvests');
        $this->assertEquals('pending', $job->harvests->first()->pivot->status);
    }

    public function test_logistics_cannot_reject_proposal(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $farmerF = $this->createFarmerUser();
        $job = $this->createJob($logisticsUser);

        $category = \App\Models\CropCategory::firstOrCreate(['name' => 'Grains'], ['status' => 'active']);
        $crop = \App\Models\Crop::firstOrCreate(['name' => 'Rice'], ['crop_category_id' => $category->id, 'status' => 'active']);
        $variety = \App\Models\CropVariety::firstOrCreate(['crop_id' => $crop->id, 'name' => 'Standard'], ['status' => 'active']);

        $harvest = \App\Models\Harvest::create([
            'user_id'               => $farmerF->id,
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'crop_category_id'      => $category->id,
            'crop_type'             => $crop->name,
            'variety'               => $variety->name,
            'quantity_kg'           => 100,
            'remaining_quantity_kg' => 100,
            'unit'                  => 'kg',
            'status'                => 'active',
            'destination_address'   => 'Test',
            'destination_latitude'  => 7.0,
            'destination_longitude' => 125.0,
        ]);

        $job->harvests()->attach($harvest->id, [
            'pickup_order' => 1,
            'quantity_kg'  => 100,
            'status'       => 'pending',
            'cost_share'   => 500,
        ]);

        $response = $this->actingAs($logisticsUser)
            ->post(route('pooling.reject', $job->id));

        $response->assertStatus(403);

        $job->refresh();
        $this->assertEquals(PoolingJobStatus::PENDING, $job->status);
        $job->load('harvests');
        $this->assertEquals('pending', $job->harvests->first()->pivot->status);
    }

    public function test_farmer_with_no_crops_gets_graceful_403(): void
    {
        $logisticsUser = $this->createLogisticsUser();
        $outsider = $this->createFarmerUser();
        $job = $this->createJob($logisticsUser);

        $response = $this->actingAs($outsider)
            ->post(route('pooling.accept', $job->id));

        $response->assertStatus(403);

        $job->refresh();
        $this->assertEquals(PoolingJobStatus::PENDING, $job->status);
    }
}
