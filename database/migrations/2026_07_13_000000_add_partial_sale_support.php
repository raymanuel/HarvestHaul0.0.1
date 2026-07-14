<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Harvests: add visibility, remaining_quantity_kg, update status enum
        Schema::table('harvests', function (Blueprint $table) {
            $table->enum('visibility', ['buyers_only', 'logistics_only', 'both'])->default('both')->after('status');
            $table->decimal('remaining_quantity_kg', 10, 2)->nullable()->after('quantity_kg');
        });

        // Negotiations: add destination fields for deal-specific drop-off
        Schema::table('negotiations', function (Blueprint $table) {
            $table->text('destination_address')->nullable()->after('status');
            $table->decimal('destination_latitude', 10, 8)->nullable()->after('destination_address');
            $table->decimal('destination_longitude', 11, 8)->nullable()->after('destination_latitude');
        });

        // Update existing harvests: set remaining_quantity_kg = quantity_kg
        DB::table('harvests')
            ->whereNull('remaining_quantity_kg')
            ->update(['remaining_quantity_kg' => DB::raw('quantity_kg')]);
    }

    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'remaining_quantity_kg']);
        });

        Schema::table('negotiations', function (Blueprint $table) {
            $table->dropColumn(['destination_address', 'destination_latitude', 'destination_longitude']);
        });
    }
};
