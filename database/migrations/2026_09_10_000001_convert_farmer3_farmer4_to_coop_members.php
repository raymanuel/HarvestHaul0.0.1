<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $coop = DB::table('logistics_profiles')
            ->where('business_permit_no', 'BP-2026-001A')
            ->first();

        if (!$coop) {
            return;
        }

        DB::table('farmer_profiles')
            ->join('users', 'users.id', '=', 'farmer_profiles.user_id')
            ->whereIn('users.email', ['farmer3@test.com', 'farmer4@test.com'])
            ->update([
                'farmer_profiles.affiliation_type' => 'cooperative',
                'farmer_profiles.cooperative_id'   => $coop->id,
                'users.affiliation_type'           => 'cooperative',
                'users.cooperative_id'             => $coop->id,
            ]);
    }

    public function down(): void
    {
        // Intentionally not reversible — only the seeder's fresh-install value changes.
    }
};