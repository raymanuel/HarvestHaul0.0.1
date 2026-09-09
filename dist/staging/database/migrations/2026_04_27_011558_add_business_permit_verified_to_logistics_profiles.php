<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_profiles', function (Blueprint $table) {
            $table->boolean('business_permit_verified')->default(false)->after('business_permit_no');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_profiles', function (Blueprint $table) {
            $table->dropColumn('business_permit_verified');
        });
    }
};
