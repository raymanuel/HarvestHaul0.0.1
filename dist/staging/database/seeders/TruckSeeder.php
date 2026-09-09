<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LogisticsProfile;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TruckSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Truck::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $cooperatives = LogisticsProfile::where('logistics_type', 'cooperative')->get();
        $companies = LogisticsProfile::where('logistics_type', 'company')->get();

        // 1. TARBC Trucks (4 Trucks: 3 Assigned, 1 Unassigned for UI Demo)
        if ($tarbc = $cooperatives->first()) {
            $drivers = User::where('role', 'driver')
                ->whereHas('driverProfile', fn($q) => $q->where('partner_id', $tarbc->id))
                ->get();

            $this->createTruck($tarbc->id, $drivers[0]->id ?? null, 'Isuzu Elf Dropside', 'RMP-1011', 'Light Truck', 2500);
            $this->createTruck($tarbc->id, $drivers[1]->id ?? null, 'Fuso Canter',        'RMP-1012', 'Light Truck', 3000);
            $this->createTruck($tarbc->id, $drivers[2]->id ?? null, 'Isuzu Forward',      'RMP-1013', 'Medium Truck', 4500);
            $this->createTruck($tarbc->id, null,                    'Kia Bongo',          'RMP-1014', 'Utility', 1500); // UNASSIGNED
        }

        // 2. CFTMPC Trucks (1 Wing Van)
        if ($cftmpc = $cooperatives->skip(1)->first()) {
            $drivers = User::where('role', 'driver')
                ->whereHas('driverProfile', fn($q) => $q->where('partner_id', $cftmpc->id))
                ->get();

            $this->createTruck($cftmpc->id, $drivers[0]->id ?? null, 'Hino 700 Wing Van', 'CFT-9999', 'Heavy Wing Van', 15000);
        }

        // 3. Commercial Company Trucks (2 Trucks: 1 Assigned, 1 Unassigned)
        if ($company = $companies->first()) {
            $drivers = User::where('role', 'driver')
                ->whereHas('driverProfile', fn($q) => $q->where('partner_id', $company->id))
                ->get();

            $this->createTruck($company->id, $drivers[0]->id ?? null, 'Fuso Fighter Dropside', 'COM-7777', 'Medium Truck', 6000);
            $this->createTruck($company->id, null,                    'Isuzu Giga',            'COM-8888', 'Heavy Truck', 12000); // UNASSIGNED
        }
    }

    private function createTruck($partnerId, $driverId, $name, $plate, $type, $capacity)
    {
        Truck::create([
            'logistics_profile_id' => $partnerId,
            'driver_id'            => $driverId,
            'truck_name'           => $name,
            'plate_number'         => $plate,
            'vehicle_type'         => $type,
            'capacity_kg'          => $capacity,
            'status'               => 'available',
        ]);
    }
}
