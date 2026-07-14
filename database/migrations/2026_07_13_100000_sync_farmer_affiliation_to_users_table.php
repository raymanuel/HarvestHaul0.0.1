<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sync users.affiliation_type and users.cooperative_id from farmer_profiles
        // for all existing farmer accounts where the two tables disagree.
        DB::table('users')
            ->join('farmer_profiles', 'users.id', '=', 'farmer_profiles.user_id')
            ->where('users.role', 'farmer')
            ->where(function ($q) {
                $q->where('users.affiliation_type', '!=', DB::raw('farmer_profiles.affiliation_type'))
                  ->orWhere('users.cooperative_id', '!=', DB::raw('farmer_profiles.cooperative_id'))
                  ->orWhereNull('users.cooperative_id');
            })
            ->whereNotNull('farmer_profiles.affiliation_type')
            ->update([
                'users.affiliation_type' => DB::raw('farmer_profiles.affiliation_type'),
                'users.cooperative_id'   => DB::raw('farmer_profiles.cooperative_id'),
            ]);
    }

    public function down(): void
    {
        // No reverse — the old data was already inconsistent.
    }
};
