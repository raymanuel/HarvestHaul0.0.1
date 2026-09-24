<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // 3-step dance: a direct shrink from the legacy enum
            // ('admin', 'driver', 'logistics_partner', ...) truncates any
            // surviving row and fails a live-DB upgrade. Widen to the union
            // first, remap legacy values into the new set, then shrink.
            $union = "ENUM('super_admin','coop_admin','field_receiving','delivery_personnel','farmer','buyer','admin','logistics_partner','driver')";
            $final = "ENUM('super_admin', 'coop_admin', 'field_receiving', 'delivery_personnel', 'farmer', 'buyer')";

            DB::statement("ALTER TABLE users MODIFY COLUMN role {$union} NOT NULL");

            DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin']);
            DB::table('users')->where('role', 'driver')->update(['role' => 'delivery_personnel']);
            DB::table('users')->where('role', 'logistics_partner')->update(['role' => 'delivery_personnel']);

            DB::statement("ALTER TABLE users MODIFY COLUMN role {$final} NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $union = "ENUM('super_admin','coop_admin','field_receiving','delivery_personnel','farmer','buyer','admin','logistics_partner','driver')";
            $legacy = "ENUM('admin', 'farmer', 'logistics_partner', 'driver', 'buyer')";

            DB::statement("ALTER TABLE users MODIFY COLUMN role {$union} NOT NULL");

            DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);
            DB::table('users')->where('role', 'delivery_personnel')->update(['role' => 'driver']);
            DB::table('users')->where('role', 'coop_admin')->update(['role' => 'admin']);
            DB::table('users')->where('role', 'field_receiving')->update(['role' => 'driver']);

            DB::statement("ALTER TABLE users MODIFY COLUMN role {$legacy} NOT NULL");
        }
    }
};