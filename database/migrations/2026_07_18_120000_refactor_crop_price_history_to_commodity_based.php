<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crop_price_history', function (Blueprint $table) {
            // Step 1: Drop FK constraint if it exists (may already be dropped)
            try {
                $table->dropForeign(['crop_id']);
            } catch (\Exception $e) {
                // FK already dropped or never existed
            }

            // Step 2: Drop old unique constraint
            $table->dropUnique(['crop_id', 'source', 'source_date']);

            // Step 3: Make crop_id nullable
            $table->foreignId('crop_id')->nullable()->change();

            // Step 4: Add commodity-level columns
            $table->string('commodity_name')->after('crop_id');
            $table->string('commodity_category')->after('commodity_name');
            $table->decimal('low_price', 10, 2)->nullable()->after('price_per_kg');
            $table->decimal('high_price', 10, 2)->nullable()->after('low_price');
            $table->decimal('common_price', 10, 2)->nullable()->after('high_price');

            // Step 5: New unique constraint on commodity name + source + date
            $table->unique(['commodity_name', 'source', 'source_date']);
            $table->index('commodity_category');
        });
    }

    public function down(): void
    {
        Schema::table('crop_price_history', function (Blueprint $table) {
            $table->dropUnique(['commodity_name', 'source', 'source_date']);
            $table->dropIndex(['source', 'source_date']);
            $table->dropIndex(['commodity_category']);
            $table->dropColumn(['commodity_name', 'commodity_category', 'low_price', 'high_price', 'common_price']);

            $table->foreignId('crop_id')->nullable(false)->change();
            $table->unique(['crop_id', 'source', 'source_date']);
            $table->foreign('crop_id')->references('id')->on('crops')->cascadeOnDelete();
        });
    }
};
