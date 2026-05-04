<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE harvests MODIFY COLUMN status ENUM('pending','active','assigned','in_progress','completed','cancelled') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE harvests MODIFY COLUMN status ENUM('pending','active','completed','cancelled') DEFAULT 'pending'");
    }
};
