<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('negotiations', function (Blueprint $table) {
            $table->decimal('hauling_rate_per_kg', 10, 2)->nullable()->after('negotiated_volume');
        });
    }

    public function down(): void
    {
        Schema::table('negotiations', function (Blueprint $table) {
            $table->dropColumn('hauling_rate_per_kg');
        });
    }
};
