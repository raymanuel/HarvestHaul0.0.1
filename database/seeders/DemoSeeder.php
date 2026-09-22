<?php

namespace Database\Seeders;

use App\Models\Cooperative;
use App\Models\CropGrade;
use App\Models\DriverProfile;
use App\Models\FarmerProfile;
use App\Models\PackagingType;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $superAdmin = User::create([
            'name' => 'Platform Super Admin',
            'email' => 'super@harvesthaul.test',
            'password' => $password,
            'role' => UserRole::SUPER_ADMIN->value,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $approved = Cooperative::create([
            'name' => 'Davao Farmers Agricultural Cooperative',
            'type' => 'primary',
            'province' => 'Davao del Sur',
            'city' => 'Davao City',
            'barangay' => 'Baguio',
            'street_address' => 'Purok 3, Baguio District',
            'contact_number' => '0917-000-0001',
            'official_email' => 'office@davaofarmers.test',
            'year_established' => 2005,
            'business_activities' => 'Crop procurement, consolidation, and B2B distribution.',
            'cda_registration_number' => 'CDA-REG-0001',
            'registration_date' => now()->subYears(19),
            'rep_name' => 'Maria Santos',
            'rep_position' => 'Cooperative Manager',
            'rep_contact' => '0917-000-0001',
            'rep_email' => 'maria@davaofarmers.test',
            'status' => Cooperative::STATUS_APPROVED,
            'reviewer_id' => $superAdmin->id,
            'reviewed_at' => now(),
            'latitude' => '7.1907',
            'longitude' => '125.4553',
        ]);

        $coopAdmin = User::create([
            'name' => 'Maria Santos',
            'email' => 'coop@harvesthaul.test',
            'password' => $password,
            'role' => UserRole::COOP_ADMIN->value,
            'status' => 'active',
            'phone' => '0917-000-0001',
            'cooperative_id' => $approved->id,
            'email_verified_at' => now(),
        ]);
        $approved->update(['coop_admin_user_id' => $coopAdmin->id]);

        Cooperative::create([
            'name' => 'South Cotabato Growers Cooperative',
            'type' => 'primary',
            'province' => 'South Cotabato',
            'city' => 'Koronadal',
            'barangay' => 'Poblacion',
            'contact_number' => '0918-000-0002',
            'official_email' => 'office@southcotabato.test',
            'year_established' => 2012,
            'cda_registration_number' => 'CDA-REG-0002',
            'rep_name' => 'Juan Dela Cruz',
            'rep_position' => 'Chairperson',
            'rep_contact' => '0918-000-0002',
            'rep_email' => 'juan@southcotabato.test',
            'status' => Cooperative::STATUS_PENDING,
        ]);

        $field = User::create([
            'name' => 'Pedro Reyes',
            'email' => 'field@harvesthaul.test',
            'password' => $password,
            'role' => UserRole::FIELD_RECEIVING->value,
            'status' => 'active',
            'phone' => '0917-000-0010',
            'cooperative_id' => $approved->id,
            'email_verified_at' => now(),
        ]);

        $delivery = User::create([
            'name' => 'Andres Lim',
            'email' => 'delivery@harvesthaul.test',
            'password' => $password,
            'role' => UserRole::DELIVERY_PERSONNEL->value,
            'status' => 'active',
            'phone' => '0917-000-0011',
            'cooperative_id' => $approved->id,
            'email_verified_at' => now(),
        ]);

        DriverProfile::create([
            'user_id' => $delivery->id,
            'cooperative_id' => $approved->id,
            'phone' => '0917-000-0011',
            'license_no' => 'N01-23-456789',
            'vehicle_type' => 'Light Truck',
            'employment_status' => 'active',
            'identity_verified' => true,
        ]);

        foreach ([
            ['Rosa Mendoza', 'farmer1@harvesthaul.test', '0917-000-0020', 'approved'],
            ['Ben Aquino', 'farmer2@harvesthaul.test', '0917-000-0021', 'approved'],
            ['Lito Garcia', 'farmer3@harvesthaul.test', '0917-000-0022', 'pending'],
        ] as [$name, $email, $phone, $membership]) {
            $farmer = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => UserRole::FARMER->value,
                'status' => 'active',
                'phone' => $phone,
                'affiliation_type' => 'cooperative',
                'cooperative_id' => $approved->id,
                'email_verified_at' => now(),
            ]);

            FarmerProfile::create([
                'user_id' => $farmer->id,
                'phone' => $phone,
                'is_verified' => $membership === 'approved',
                'farm_location' => 'Baguio District, Davao City',
                'latitude' => '7.1800',
                'longitude' => '125.4400',
                'affiliation_type' => 'cooperative',
                'cooperative_id' => $approved->id,
                'membership_status' => $membership,
                'membership_requested_at' => now(),
                'membership_decided_at' => $membership === 'approved' ? now() : null,
            ]);
        }

        $verifiedBuyer = User::create([
            'name' => 'MetroMart Fresh Produce',
            'email' => 'buyer@harvesthaul.test',
            'password' => $password,
            'role' => UserRole::BUYER->value,
            'status' => 'active',
            'phone' => '0917-000-0030',
            'email_verified_at' => now(),
        ]);
        $verifiedBuyer->buyerProfile()->create([
            'business_name' => 'MetroMart Fresh Produce',
            'contact_person' => 'Carla Dizon',
            'phone' => '0917-000-0030',
            'business_address' => 'Warehouse 4, Sasa, Davao City',
            'is_verified' => true,
        ]);

        $pendingBuyer = User::create([
            'name' => 'Green Basket Trading',
            'email' => 'buyer2@harvesthaul.test',
            'password' => $password,
            'role' => UserRole::BUYER->value,
            'status' => 'active',
            'phone' => '0917-000-0031',
            'email_verified_at' => now(),
        ]);
        $pendingBuyer->buyerProfile()->create([
            'business_name' => 'Green Basket Trading',
            'contact_person' => 'Noel Tan',
            'phone' => '0917-000-0031',
            'business_address' => 'Public Market, Koronadal',
            'is_verified' => false,
        ]);

        foreach ([
            ['Hauler 1', 'ABC-1234', 3000],
            ['Hauler 2', 'XYZ-5678', 1500],
        ] as [$truckName, $plate, $capacity]) {
            Truck::create([
                'cooperative_id' => $approved->id,
                'driver_id' => $delivery->id,
                'truck_name' => $truckName,
                'plate_number' => $plate,
                'vehicle_type' => 'Light Truck',
                'capacity_kg' => $capacity,
                'status' => 'available',
            ]);
        }

        foreach ([
            ['Premium', 'A', 1],
            ['Regular', 'B', 2],
            ['Economy', 'C', 3],
            ['Reject', 'R', 4],
        ] as [$name, $code, $order]) {
            CropGrade::create(['name' => $name, 'code' => $code, 'sort_order' => $order]);
        }

        foreach ([
            ['Sack (50 kg)', 1],
            ['Crate', 2],
            ['Bundle', 3],
        ] as [$name, $order]) {
            PackagingType::create(['name' => $name, 'sort_order' => $order]);
        }
    }
}