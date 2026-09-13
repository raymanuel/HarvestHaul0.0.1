<?php

namespace App\Console\Commands;

use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\CustomerCard;
use App\Models\DriverProfile;
use App\Models\FarmerProfile;
use App\Models\Harvest;
use App\Models\LogisticsProfile;
use App\Models\Negotiation;
use App\Models\NegotiationStatus;
use App\Models\OutboundOrder;
use App\Models\OutboundOrderLine;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use App\Models\TrackingRecord;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedOutboundDemo extends Command
{
    protected $signature = 'outbound:demo-seed';

    protected $description = 'Seed a shareable end-to-end outbound distribution demo (coop, farmer, completed negotiation, customer card, dispatched order with tracking).';

    public function handle(): int
    {
        $password = 'demo1234';

        // 1. Cooperative logistics user (verified, near GenSan).
        $coopUser = User::updateOrCreate(
            ['email' => 'demo.outbound.coop@harvesthaul.app'],
            [
                'name'              => 'DEMO Outbound Agri COOP',
                'password'          => Hash::make($password),
                'role'              => 'logistics_partner',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        $coop = LogisticsProfile::updateOrCreate(
            ['user_id' => $coopUser->id],
            [
                'company_name'    => 'DEMO Outbound Agri COOP',
                'business_permit_no' => 'DEMO-OB-BP-0001',
                'cda_registration_no' => 'DEMO-OB-CDA-0001',
                'phone'           => '09170001001',
                'is_verified'     => true,
                'logistics_type'  => 'cooperative',
                'office_address'  => 'General Santos City, South Cotabato',
                'latitude'        => 6.0533,
                'longitude'       => 125.1321,
            ]
        );

        // 2. Farmer affiliated to the coop, plus a completed negotiation/harvest deal.
        $farmerUser = User::updateOrCreate(
            ['email' => 'demo.outbound.farmer@harvesthaul.app'],
            [
                'name'              => 'DEMO Farmer Rosa Dizon',
                'password'          => Hash::make($password),
                'role'              => 'farmer',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        FarmerProfile::updateOrCreate(
            ['user_id' => $farmerUser->id],
            [
                'phone'             => '09170001004',
                'farm_location'     => 'Polomolok, South Cotabato',
                'is_verified'       => true,
                'latitude'          => 6.2215,
                'longitude'         => 125.0718,
                'affiliation_type'  => 'cooperative',
                'cooperative_id'    => $coop->id,
                'membership_status' => 'approved',
            ]
        );

        $category = CropCategory::updateOrCreate(['name' => 'Fruits'], ['status' => 'active']);
        $crop = Crop::updateOrCreate(['name' => 'Banana'], ['crop_category_id' => $category->id, 'status' => 'active']);
        $variety = CropVariety::updateOrCreate(['crop_id' => $crop->id, 'name' => 'Saba'], ['status' => 'active']);

        $harvest = Harvest::updateOrCreate(
            ['user_id' => $farmerUser->id],
            [
                'crop_category_id'       => $category->id,
                'crop_id'                => $crop->id,
                'crop_variety_id'        => $variety->id,
                'crop_type'              => 'Banana',
                'variety'                => 'Saba',
                'quantity_kg'            => 500,
                'unit'                   => 'kg',
                'status'                 => 'sold',
                'visibility'             => 'both',
                'notes'                  => 'DEMO completed inbound deal for outbound distribution.',
                'harvest_date'           => now()->toDateString(),
                'quality_grade'          => 'A',
                'suggested_price_per_kg' => 20,
                'latitude'               => 6.2215,
                'longitude'              => 125.0718,
                'destination_address'    => 'General Santos City, South Cotabato',
                'destination_latitude'   => 6.1146,
                'destination_longitude'  => 125.1717,
            ]
        );

        Negotiation::updateOrCreate(
            ['buyer_id' => $coopUser->id, 'farmer_id' => $farmerUser->id, 'harvest_id' => $harvest->id],
            [
                'negotiated_price'          => 10000,
                'negotiated_volume'         => 500,
                'hauling_rate_per_kg'       => 3.00,
                'status'                    => NegotiationStatus::COMPLETED->value,
                'destination_address'       => 'General Santos City, South Cotabato',
                'destination_latitude'      => 6.1146,
                'destination_longitude'     => 125.1717,
                'last_activity_at'          => now(),
            ]
        );

        // 3. A driver + truck owned by the coop.
        $driverUser = User::updateOrCreate(
            ['email' => 'demo.outbound.driver@harvesthaul.app'],
            [
                'name'              => 'DEMO Driver Ely Guzman',
                'password'          => Hash::make($password),
                'role'              => 'driver',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        DriverProfile::updateOrCreate(
            ['user_id' => $driverUser->id],
            [
                'partner_id' => $coop->id,
                'license_no' => 'DEMO-OB-DV-0001',
                'phone'      => '09170001002',
                'status'     => 'active',
            ]
        );

        $truck = Truck::updateOrCreate(
            ['logistics_profile_id' => $coop->id, 'plate_number' => 'DEMO-OB-001'],
            [
                'truck_name'          => 'DEMO Outbound Truck',
                'vehicle_type'        => 'truck',
                'capacity_kg'         => 2000,
                'status'              => 'available',
            ]
        );

        // 4. Customer card.
        $customer = CustomerCard::updateOrCreate(
            ['logistics_profile_id' => $coop->id, 'name' => 'Robinsons Place General Santos'],
            [
                'business_type' => 'grocery',
                'contact'       => '09170001003',
                'address'       => 'National Highway, Barangay Lagao, General Santos City, South Cotabato',
                'latitude'      => 6.1146,
                'longitude'     => 125.1717,
            ]
        );

        // 5. Dispatched outbound order (confirmed, token minted) + outbound job + tracking route.
        //    The token and dispatch timestamp are preserved across re-runs so shared tracking links stay valid.
        $existing = OutboundOrder::where('customer_card_id', $customer->id)
            ->where('logistics_profile_id', $coop->id)
            ->first();

        $order = OutboundOrder::updateOrCreate(
            ['customer_card_id' => $customer->id, 'logistics_profile_id' => $coop->id],
            [
                'status'         => 'confirmed',
                'total_kg'       => 150,
                'total_amount'   => 3000,
                'tracking_token' => $existing?->tracking_token ?? Str::random(32),
                'dispatched_at'  => $existing?->dispatched_at ?? now(),
                'notes'          => 'DEMO Banana order from the completed coop B2B deal.',
            ]
        );

        OutboundOrderLine::updateOrCreate(
            ['outbound_order_id' => $order->id, 'crop_type' => 'Banana'],
            [
                'quantity_kg' => 150,
                'rate_per_kg' => 20,
                'subtotal'    => 3000,
            ]
        );

        $job = PoolingJob::updateOrCreate(
            ['outbound_order_id' => $order->id],
            [
                'logistics_profile_id' => $coop->id,
                'truck_id'             => $truck->id,
                'driver_id'            => $driverUser->id,
                'status'               => PoolingJobStatus::CONFIRMED->value,
                'leg_type'             => 'outbound',
                'customer_card_id'     => $customer->id,
                'total_kg'             => 150,
                'truck_capacity_kg'    => 2000,
                'start_latitude'       => 6.0533,
                'start_longitude'      => 125.1321,
                'end_latitude'         => 6.1146,
                'end_longitude'        => 125.1717,
                'radius_km'            => 5,
                'price_reference'      => 3000,
                'confirmed_at'         => now(),
                'notes'                => $order->notes,
            ]
        );

        // A handful of GPS points progressing from the coop to the customer so the live map has a route.
        TrackingRecord::where('pooling_job_id', $job->id)->delete();

        $route = [
            [6.0533, 125.1321, now()->subMinutes(60)],
            [6.0830, 125.1460, now()->subMinutes(40)],
            [6.1010, 125.1590, now()->subMinutes(20)],
            [6.1146, 125.1717, now()->subMinutes(5)],
        ];

        foreach ($route as [$lat, $lng, $postedAt]) {
            TrackingRecord::create([
                'pooling_job_id'    => $job->id,
                'driver_id'         => $driverUser->id,
                'latitude'          => $lat,
                'longitude'         => $lng,
                'speed_kmh'         => 35,
                'bearing'           => 45,
                'accuracy_meters'   => 8,
                'posted_at'         => $postedAt,
            ]);
        }

        $this->info('Outbound demo data ready. Re-running keeps the same records (idempotent).');
        $this->info('Emails: demo.outbound.coop@ / demo.outbound.farmer@ / demo.outbound.driver@ — all @harvesthaul.app, password: ' . $password);
        $this->info('Order #' . $order->id . ' (Route #' . $job->id . '). Tracking link: ' . route('outbound.track', $order->tracking_token));

        return self::SUCCESS;
    }
}