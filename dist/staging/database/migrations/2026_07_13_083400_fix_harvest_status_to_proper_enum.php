<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE harvests MODIFY COLUMN status ENUM('pending','active','sold','negotiating','partially_sold','assigned','in_progress','completed','cancelled') DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE harvests MODIFY COLUMN status VARCHAR(255) DEFAULT 'active'");
        }
    }
};
