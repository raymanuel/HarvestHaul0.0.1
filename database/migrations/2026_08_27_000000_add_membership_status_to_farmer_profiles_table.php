<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            $table->string('membership_status')->nullable()->default(null);
            $table->timestamp('membership_requested_at')->nullable();
            $table->timestamp('membership_decided_at')->nullable();
        });

        DB::table('farmer_profiles')
            ->whereNotNull('cooperative_id')
            ->update([
                'membership_status'     => 'approved',
                'membership_decided_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'membership_status',
                'membership_requested_at',
                'membership_decided_at',
            ]);
        });
    }
};
