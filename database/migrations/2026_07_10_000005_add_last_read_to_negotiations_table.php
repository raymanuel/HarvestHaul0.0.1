<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('negotiations', function (Blueprint $table) {
            $table->timestamp('buyer_last_read_at')->nullable()->after('last_activity_at');
            $table->timestamp('farmer_last_read_at')->nullable()->after('buyer_last_read_at');
        });
    }

    public function down(): void
    {
        Schema::table('negotiations', function (Blueprint $table) {
            $table->dropColumn(['buyer_last_read_at', 'farmer_last_read_at']);
        });
    }
};
