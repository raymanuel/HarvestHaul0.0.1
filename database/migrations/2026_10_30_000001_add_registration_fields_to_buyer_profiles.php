<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buyer_profiles', function (Blueprint $table) {
            // business_name/contact_person/business_address already exist
            // (added by 2026_10_19_000010_repurpose_existing_tables.php) —
            // only business_information and the real 4-state status are new.
            $table->text('business_information')->nullable()->after('business_address');
            $table->string('status', 16)->default('pending')->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('buyer_profiles', function (Blueprint $table) {
            $table->dropColumn(['business_information', 'status']);
        });
    }
};
