<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\Destination;
use App\Models\DriverProfile;
use App\Models\FarmerProfile;
use App\Models\Harvest;
use App\Models\LogisticsProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for the pilot/launch DB on Hostinger.
 *
 * Everything created here is labeled with a "DEMO" prefix so it is easy to
 * spot. The seeder is idempotent — it may be re-run safely: users/profiles are
 * updated in place and harvests are only created when a demo farmer has none.
 *
 * When going live for real users, wipe everything with:
 *   php artisan migrate:fresh --force
 */
class DemoSeeder extends Seeder
{
    public const DEMO_PREFIX = 'DEMO';

    public const DEMO_PASSWORD = 'demo1234';

    public function run(): void
    {
        // ---------------------------------------------------------------
        // 0) Base catalog (crops, varieties, destinations) — only on a
        //    fresh DB, so re-runs never touch crops users added later.
        // ---------------------------------------------------------------
        if (Crop::count() === 0) {
            $this->call(CropSeeder::class);
            $this->call(DestinationSeeder::class);
        }

        // ---------------------------------------------------------------
        // 1) Admins (5 demo accounts)
        // ---------------------------------------------------------------
        $this->upsertUser('DEMO Admin', 'demo.admin@harvesthaul.app', 'admin');
        $this->upsertUser('DEMO Admin Ray', 'demo.adminray@harvesthaul.app', 'admin');
        $this->upsertUser('DEMO Admin Iver', 'demo.adminiver@harvesthaul.app', 'admin');
        $this->upsertUser('DEMO Admin Jake', 'demo.adminjake@harvesthaul.app', 'admin');
        $this->upsertUser('DEMO Admin Gab', 'demo.admingab@harvesthaul.app', 'admin');

        // ---------------------------------------------------------------
        // 2) Logistics partner (cooperative) — the demo hauling hub
        // ---------------------------------------------------------------
        $logisticsUser = $this->upsertUser(
            'DEMO South Cotabato Agri Transport COOP',
            'demo.coop@harvesthaul.app',
            'logistics_partner'
        );

        $coop = LogisticsProfile::updateOrCreate(
            ['user_id' => $logisticsUser->id],
            [
                'company_name'       => 'DEMO South Cotabato Agri Transport COOP',
                'business_permit_no' => 'DEMO-BP-0001',
                'cda_registration_no' => 'DEMO-CDA-0001',
                'phone'              => '09170000001',
                'is_verified'        => true,
                'logistics_type'     => 'cooperative',
                'office_address'     => 'General Santos City, South Cotabato',
                'latitude'           => 6.1050,
                'longitude'          => 125.1830,
                'default_hauling_rate' => 3.50,
            ]
        );

        // ---------------------------------------------------------------
        // 3) Demo farmers (two coop members + one independent)
        // ---------------------------------------------------------------
        $farmerAna = $this->upsertUser('DEMO Farmer Ana Banag', 'demo.farmer1@harvesthaul.app', 'farmer');
        $this->upsertFarmer($farmerAna, 'Polomolok, South Cotabato', 6.2215, 125.0718, $coop->id);

        $farmerBen = $this->upsertUser('DEMO Farmer Ben Tupas', 'demo.farmer2@harvesthaul.app', 'farmer');
        $this->upsertFarmer($farmerBen, 'Tupi, South Cotabato', 6.3333, 124.9416, $coop->id);

        $farmerCelia = $this->upsertUser('DEMO Farmer Celia Nograles', 'demo.farmer3@harvesthaul.app', 'farmer');
        $this->upsertFarmer($farmerCelia, 'Lagao, General Santos City', 6.1351, 125.1912, null);

        // ---------------------------------------------------------------
        // 4) Demo buyer
        // ---------------------------------------------------------------
        $this->upsertUser('DEMO Buyer Landmark Trading', 'demo.buyer@harvesthaul.app', 'buyer');

        // ---------------------------------------------------------------
        // 5) Demo driver (belongs to the demo cooperative)
        // ---------------------------------------------------------------
        $driverUser = $this->upsertUser('DEMO Driver Eliseo', 'demo.driver@harvesthaul.app', 'driver');
        DriverProfile::updateOrCreate(
            ['user_id' => $driverUser->id],
            [
                'partner_id' => $coop->id,
                'license_no' => 'DEMO-DV-0001',
                'phone'      => '09170000002',
            ]
        );

        // ---------------------------------------------------------------
        // 6) A few active harvests so the boards are not empty.
        //    Only created when a demo farmer has none yet.
        // ---------------------------------------------------------------
        $market = Destination::where('name', 'General Santos Public Market')->first();
        foreach ([[$farmerAna, 'Tomato', 'Local', 500, 45], [$farmerBen, 'Banana', 'Saba', 1200, 28]] as [$farmer, $cropName, $varietyName, $kg, $price]) {
            if (Harvest::where('user_id', $farmer->id)->exists()) {
                continue;
            }

            $crop = Crop::where('name', $cropName)->first();
            if (!$crop) {
                continue;
            }

            $variety = $crop->varieties()->where('name', $varietyName)->first();
            $now = Carbon::now();

            Harvest::create([
                'user_id'                    => $farmer->id,
                'crop_category_id'           => $crop->crop_category_id,
                'crop_id'                    => $crop->id,
                'crop_variety_id'            => $variety?->id,
                'crop_type'                  => $crop->name,
                'variety'                    => $variety?->name,
                'quantity_kg'                => $kg,
                'estimated_volume_cubic_m'   => round($kg / 350, 2),
                'unit'                       => 'kg',
                'status'                     => 'active',
                'visibility'                 => 'both',
                'notes'                      => 'DEMO post for the pilot launch.',
                'harvest_date'               => $now->toDateString(),
                'pickup_window_start'        => $now->copy()->addDay(),
                'pickup_window_end'          => $now->copy()->addDays(2),
                'quality_grade'              => 'A',
                'suggested_price_per_kg'     => $price,
                'latitude'                   => $farmer->farmerProfile->latitude,
                'longitude'                  => $farmer->farmerProfile->longitude,
                'destination_id'             => $market?->id,
                'destination_address'        => $market?->address ?? 'General Santos City, South Cotabato',
                'destination_latitude'       => $market?->latitude ?? 6.1108,
                'destination_longitude'      => $market?->longitude ?? 125.1716,
            ]);
        }

        $this->command?->info('Demo data ready. Login emails: demo.admin@ / demo.adminray@ / demo.adminiver@ / demo.adminjake@ / demo.admingab@ / demo.coop@ / demo.farmer1@ / demo.farmer2@ / demo.farmer3@ / demo.buyer@ / demo.driver@ — all @harvesthaul.app, password: ' . self::DEMO_PASSWORD);
    }

    private function upsertUser(string $name, string $email, string $role): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make(self::DEMO_PASSWORD),
                'role'              => $role,
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );
    }

    private function upsertFarmer(User $user, string $farmLocation, float $lat, float $lng, ?int $coopId): void
    {
        FarmerProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone'             => $user->id ? ('09' . str_pad((string) (100000000 + $user->id), 9, '0', STR_PAD_LEFT)) : null,
                'farm_location'     => $farmLocation,
                'is_verified'       => true,
                'latitude'          => $lat,
                'longitude'         => $lng,
                'affiliation_type'  => $coopId ? 'cooperative' : 'independent',
                'cooperative_id'    => $coopId,
                'membership_status' => $coopId ? 'approved' : null,
            ]
        );
    }
}