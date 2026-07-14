<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE harvests MODIFY COLUMN status VARCHAR(255) DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        // No-op: column type unchanged
    }
};
