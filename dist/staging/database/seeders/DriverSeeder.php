<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\LogisticsProfile;
use App\Models\DriverProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DriverSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DriverProfile::truncate();
        User::where('role', 'driver')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $now = Carbon::now();
        $password = Hash::make('password');

        $cooperatives = LogisticsProfile::where('logistics_type', 'cooperative')->get();
        $companies = LogisticsProfile::where('logistics_type', 'company')->get();

        if ($cooperatives->isEmpty() && $companies->isEmpty()) return;

        // TARBC Drivers (Includes one extra "Idle" driver for future UI testing)
        if ($tarbc = $cooperatives->first()) {
            $this->seedDrivers($tarbc, ['Eliseo Driver', 'Mario Driver', 'Julio Driver', 'Nestor Driver', 'Idle TARBC Driver'], $password, $now);
        }

        // CFTMPC Drivers
        if ($cftmpc = $cooperatives->skip(1)->first()) {
            $this->seedDrivers($cftmpc, ['Cardo Dalisay', 'Idle CFTMPC Driver'], $password, $now);
        }

        // Commercial Company Drivers
        if ($company = $companies->first()) {
            $this->seedDrivers($company, ['Private Driver 1', 'Private Driver 2'], $password, $now);
        }
    }

    private function seedDrivers(LogisticsProfile $partner, array $names, string $password, Carbon $now)
    {
        foreach ($names as $name) {
            // Fix: Converts "Private Driver 1" into "private-driver-1-7@driver.com" for guaranteed uniqueness
            $emailPrefix = Str::slug($name) . '-' . $partner->id . '@driver.com';

            $driverUser = User::create([
                'name'              => $name,
                'email'             => $emailPrefix,
                'password'          => $password,
                'role'              => 'driver',
                'email_verified_at' => $now,
            ]);

            DriverProfile::create([
                'user_id'    => $driverUser->id,
                'partner_id' => $partner->id,
                'license_no' => 'D0' . $partner->id . '-' . rand(100000, 999999),
                'phone'      => '09' . rand(100000000, 999999999),
            ]);
        }
    }
}
