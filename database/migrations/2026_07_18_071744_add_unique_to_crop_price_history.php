<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('crop_price_history', function (Blueprint $table) {
            // The unique index on (crop_id, source, source_date) already covers
            // (source, source_date) as a prefix, so this redundant non-unique index can be dropped.
            $table->dropIndex('crop_price_history_source_source_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crop_price_history', function (Blueprint $table) {
            $table->index(['source', 'source_date']);
        });
    }
};
