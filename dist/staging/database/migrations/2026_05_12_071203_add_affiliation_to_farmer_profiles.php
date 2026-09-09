<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            $table->string('affiliation_type', 20)->default('independent');

            $table->foreignId('cooperative_id')
                  ->nullable()
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
