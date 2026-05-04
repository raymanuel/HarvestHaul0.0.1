<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            // FK to predefined destinations (nullable — farmer may use custom pin instead)
            $table->foreignId('destination_id')
                  ->nullable()
                  ->after('cluster_id')
                  ->constrained('destinations')
                  ->nullOnDelete();

            // Custom pinned destination (used when farmer pins a custom location)
            $table->string('destination_address')->nullable()->after('destination_id');
            $table->decimal('destination_latitude', 10, 8)->nullable()->after('destination_address');
            $table->decimal('destination_longitude', 11, 8)->nullable()->after('destination_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_id');
            $table->dropColumn([
                'destination_address',
                'destination_latitude',
                'destination_longitude',
            ]);
        });
    }
};
