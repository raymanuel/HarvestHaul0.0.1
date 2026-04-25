<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('audit_logs', function (Blueprint $table) {
        // Drop the FK constraint — target_id references multiple tables
        // depending on target_type, so it cannot be constrained to users only
        $table->dropForeign(['target_id']);

        // Change to plain unsignedBigInteger — no constraint
        $table->unsignedBigInteger('target_id')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('audit_logs', function (Blueprint $table) {
        $table->dropColumn('target_id');
        $table->foreignId('target_id')->constrained('users')->onDelete('cascade');
    });
}
};
