<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support ALTER COLUMN for enums, so we use a
        // text-based column. For MySQL, we run the alter statement:
        DB::statement("ALTER TABLE harvests MODIFY COLUMN status VARCHAR(255) DEFAULT 'active'");
    }

    public function down(): void
    {
        // No-op: column type unchanged
    }
};
