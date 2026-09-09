<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_profiles', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_profiles', function (Blueprint $table) {
            $table->dropColumn('is_verified');
        });
    }
};
