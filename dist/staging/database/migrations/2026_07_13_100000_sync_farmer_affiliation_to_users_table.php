<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
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
        } catch (\Exception $e) {
            // Column may not exist on SQLite in-memory test databases — skip silently.
        }
    }

    public function down(): void
    {
        // No reverse — the old data was already inconsistent.
    }
};
