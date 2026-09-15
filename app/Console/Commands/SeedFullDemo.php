<?php

namespace App\Console\Commands;

use App\Models\CustomerCard;
use App\Models\DriverProfile;
use App\Models\FarmerProfile;
use App\Models\Harvest;
use App\Models\LogisticsProfile;
use App\Models\OutboundOrder;
use App\Models\PoolingJob;
use App\Models\TrackingRecord;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeedFullDemo extends Command
{
    protected $signature = 'demo:full-seed';

    protected $description = 'Reset + reseed the deterministic full end-to-end demo universe (DEMO Outbound Agri COOP: 4 coop farmers, 2 inbound trucks + drivers, outbound truck/customer card). Runs before each browser demo scenario.';

    private const PASSWORD = 'demo1234';

    private const DEMO_FARMERS = [
        // email => [name, phone, farm_location, lat, lng]
        'demo.outbound.farmer@harvesthaul.app'  => ['DEMO Farmer Rosa Dizon',   '09170001004', 'Polomolok, South Cotabato', 6.2215, 125.0718],
        'demo.outbound.farmer2@harvesthaul.app' => ['DEMO Farmer Jocelyn Ramos', '09170001005', 'Tupi, South Cotabato',       6.1420, 125.1550],
        'demo.outbound.farmer3@harvesthaul.app' => ['DEMO Farmer Dante Mabuhay', '09170001006', 'Lake Sebu, South Cotabato',  6.3333, 124.9416],
        'demo.outbound.farmer4@harvesthaul.app' => ['DEMO Farmer Lito Salvador', '09170001007', 'Malungon, Sarangani',        6.1511, 125.2215],
    ];

    public function handle(): int
    {
        $coopUser = User::where('email', 'demo.outbound.coop@harvesthaul.app')->first();

        if ($coopUser) {
            $this->resetDemoData($coopUser);
        }

        // 1. Cooperative logistics user (verified, near GenSan) — idempotent.
        $coopUser = User::updateOrCreate(
            ['email' => 'demo.outbound.coop@harvesthaul.app'],
            [
                'name'              => 'DEMO Outbound Agri COOP',
                'password'          => Hash::make(self::PASSWORD),
                'role'              => 'logistics_partner',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        $coop = LogisticsProfile::updateOrCreate(
            ['user_id' => $coopUser->id],
            [
                'company_name'        => 'DEMO Outbound Agri COOP',
                'business_permit_no'  => 'DEMO-OB-BP-0001',
                'cda_registration_no' => 'DEMO-OB-CDA-0001',
                'phone'               => '09170001001',
                'is_verified'         => true,
                'logistics_type'      => 'cooperative',
                'office_address'      => 'General Santos City, South Cotabato',
                'latitude'            => 6.0533,
                'longitude'           => 125.1321,
            ]
        );

        // 2. Four coop farmers (verified, members, GPS coords for pickup stops).
        foreach (self::DEMO_FARMERS as $email => [$name, $phone, $location, $lat, $lng]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'              => $name,
                    'password'          => Hash::make(self::PASSWORD),
                    'role'              => 'farmer',
                    'status'            => 'active',
                    'email_verified_at' => now(),
                ]
            );

            FarmerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone'             => $phone,
                    'farm_location'     => $location,
                    'is_verified'       => true,
                    'latitude'          => $lat,
                    'longitude'         => $lng,
                    'affiliation_type'  => 'cooperative',
                    'cooperative_id'    => $coop->id,
                    'membership_status' => 'approved',
                ]
            );
        }

        // 3. Two inbound drivers (each bound to one inbound truck).
        $ely = User::updateOrCreate(
            ['email' => 'demo.outbound.driver@harvesthaul.app'],
            [
                'name'              => 'DEMO Driver Ely Guzman',
                'password'          => Hash::make(self::PASSWORD),
                'role'              => 'driver',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );
        DriverProfile::updateOrCreate(
            ['user_id' => $ely->id],
            [
                'partner_id' => $coop->id,
                'license_no' => 'DEMO-OB-DV-0001',
                'phone'      => '09170001002',
            ]
        );

        $driver2 = User::updateOrCreate(
            ['email' => 'demo.outbound.driver2@harvesthaul.app'],
            [
                'name'              => 'DEMO Driver Rico Bartolome',
                'password'          => Hash::make(self::PASSWORD),
                'role'              => 'driver',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );
        DriverProfile::updateOrCreate(
            ['user_id' => $driver2->id],
            [
                'partner_id' => $coop->id,
                'license_no' => 'DEMO-OB-DV-0002',
                'phone'      => '09170001008',
            ]
        );

        // 4. Trucks owned by the coop. Inbound trucks carry an assigned driver so the
        //    route planner can plan them; the outbound truck stays driverless (it is
        //    dispatched manually from the Distribution module).
        $trucks = [
            ['plate' => 'DEMO-IB-001', 'name' => 'Isuzu Elf Dropside 2.5T', 'capacity' => 2500, 'driver' => $ely->id],
            ['plate' => 'DEMO-IB-002', 'name' => 'Isuzu Forward 3T',       'capacity' => 3000, 'driver' => $driver2->id],
            ['plate' => 'DEMO-OB-001', 'name' => 'DEMO Outbound Truck',    'capacity' => 2000, 'driver' => null],
        ];

        foreach ($trucks as $truck) {
            Truck::updateOrCreate(
                ['logistics_profile_id' => $coop->id, 'plate_number' => $truck['plate']],
                [
                    'truck_name'   => $truck['name'],
                    'vehicle_type' => 'truck',
                    'capacity_kg'  => $truck['capacity'],
                    'driver_id'    => $truck['driver'],
                    'status'       => 'available',
                ]
            );
        }

        // 5. Customer card for the outbound leg.
        CustomerCard::updateOrCreate(
            ['logistics_profile_id' => $coop->id, 'name' => 'Robinsons Place General Santos'],
            [
                'business_type' => 'grocery',
                'contact'       => '09170001003',
                'address'       => 'National Highway, Barangay Lagao, General Santos City, South Cotabato',
                'latitude'      => 6.11498470,
                'longitude'     => 125.17995374,
            ]
        );

        $this->line('');
        $this->info('Full demo universe ready (idempotent, repeatable).');
        $this->info('All passwords: ' . self::PASSWORD);
        $this->info('Accounts (all @harvesthaul.app):');
        $this->info('  coop    demo.outbound.coop');
        $this->info('  farmer  demo.outbound.farmer / farmer2 / farmer3 / farmer4');
        $this->info('  driver  demo.outbound.driver (Ely, truck DEMO-IB-001 2.5T)');
        $this->info('          demo.outbound.driver2 (Rico, truck DEMO-IB-002 3T)');
        $this->info('  trucks  DEMO-IB-001 (2500kg), DEMO-IB-002 (3000kg), DEMO-OB-001 (2000kg, outbound)');
        $this->info('Customer: Robinsons Place General Santos (card #1).');

        return self::SUCCESS;
    }

    /**
     * Wipe only prior full-demo artifacts so every scenario run starts from the
     * same clean state: demo-family harvests, their negotiations/messages/pivots,
     * the finished demo outbound order + outbound job + tracking points, and any
     * truck stuck in a non-available status. Other test data is never touched.
     */
    private function resetDemoData(User $coopUser): void
    {
        $demoFarmerIds = User::where('email', 'like', 'demo.outbound.farmer%@harvesthaul.app')->pluck('id');

        $harvestIds = Harvest::withTrashed()
            ->whereIn('user_id', $demoFarmerIds)
            ->where(function ($q) {
                $q->where('notes', 'like', 'E2E DEMO%')
                    ->orWhere('notes', 'like', 'DEMO%');
            })
            ->pluck('id');

        $jobIds = DB::table('pooling_job_harvests')
            ->whereIn('harvest_id', $harvestIds)
            ->pluck('pooling_job_id')
            ->unique();

        $outboundOrderIds = OutboundOrder::withTrashed()
            ->where('logistics_profile_id', $coopUser->logisticsProfile?->id ?? 0)
            ->pluck('id');

        $outboundJobIds = PoolingJob::withTrashed()->whereIn('outbound_order_id', $outboundOrderIds)->pluck('id');

        TrackingRecord::whereIn('pooling_job_id', $jobIds->merge($outboundJobIds))->delete();

        // Demo artifacts are never needed again after a reset, so hard-delete
        // instead of soft-deleting (avoids accumulating dead rows on re-runs).
        PoolingJob::withTrashed()->whereIn('id', $jobIds)->forceDelete();
        PoolingJob::withTrashed()->whereIn('id', $outboundJobIds)->forceDelete();

        Harvest::withTrashed()->whereIn('id', $harvestIds)->forceDelete();

        if ($outboundOrderIds->isNotEmpty()) {
            OutboundOrder::withTrashed()->whereIn('id', $outboundOrderIds)->forceDelete();
        }

        $restored = Truck::where('logistics_profile_id', $coopUser->logisticsProfile?->id ?? 0)
            ->where('status', '!=', 'available')
            ->update(['status' => 'available']);

        $this->line('Reset: ' . $harvestIds->count() . ' demo harvests, ' . $jobIds->count() . ' inbound job(s), '
            . $outboundOrderIds->count() . ' outbound order(s), ' . $restored . ' truck(s) restored.');
    }
}