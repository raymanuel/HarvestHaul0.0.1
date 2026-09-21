<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buyer_orders', function (Blueprint $table) {
            $table->date('preferred_delivery_date')->nullable()->after('cooperative_id');
        });
    }

    public function down(): void
    {
        Schema::table('buyer_orders', function (Blueprint $table) {
            $table->dropColumn('preferred_delivery_date');
        });
    }
};
