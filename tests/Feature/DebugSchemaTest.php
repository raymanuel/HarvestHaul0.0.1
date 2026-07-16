<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DebugSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dump_farmer_profiles_columns_on_sqlite(): void
    {
        // Check farmer_profiles
        if (Schema::hasTable('farmer_profiles')) {
            $cols = Schema::getColumnListing('farmer_profiles');
            echo "FARMER_PROFILES COLUMNS: " . implode(', ', $cols) . "\n";
            $createSql = DB::select("SELECT sql FROM sqlite_master WHERE name = 'farmer_profiles'");
            echo "FARMER_PROFILES DDL: " . ($createSql[0]->sql ?? 'NOT FOUND') . "\n";
        } else {
            echo "FARMER_PROFILES TABLE DOES NOT EXIST\n";
        }

        // Check users
        if (Schema::hasTable('users')) {
            $cols = Schema::getColumnListing('users');
            echo "USERS COLUMNS: " . implode(', ', $cols) . "\n";
            $createSql = DB::select("SELECT sql FROM sqlite_master WHERE name = 'users'");
            echo "USERS DDL: " . ($createSql[0]->sql ?? 'NOT FOUND') . "\n";
        } else {
            echo "USERS TABLE DOES NOT EXIST\n";
        }

        $this->assertTrue(true);
    }
}
