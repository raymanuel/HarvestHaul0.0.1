<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            $table->enum('affiliation_type', ['cooperative', 'independent'])
                  ->after('longitude');

            $table->foreignId('cooperative_id')
                  ->nullable()
                  ->after('affiliation_type')
                  ->constrained('logistics_profiles')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            $table->dropForeign(['cooperative_id']);
            $table->dropColumn(['affiliation_type', 'cooperative_id']);
        });
    }
};
