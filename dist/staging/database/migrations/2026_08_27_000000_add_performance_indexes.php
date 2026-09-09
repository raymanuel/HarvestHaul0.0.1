<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->index('destination_id');
            $table->index(['crop_id', 'status']);
            $table->index(['crop_variety_id', 'status']);
        });

        Schema::table('driver_heartbeats', function (Blueprint $table) {
            $table->index(['logistics_profile_id', 'reported_at']);
        });

        Schema::table('farmer_documents', function (Blueprint $table) {
            $table->index(['document_type', 'status']);
        });

        Schema::table('logistics_documents', function (Blueprint $table) {
            $table->index(['document_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->dropIndex(['destination_id']);
            $table->dropIndex(['crop_id', 'status']);
            $table->dropIndex(['crop_variety_id', 'status']);
        });

        Schema::table('driver_heartbeats', function (Blueprint $table) {
            $table->dropIndex(['logistics_profile_id', 'reported_at']);
        });

        Schema::table('farmer_documents', function (Blueprint $table) {
            $table->dropIndex(['document_type', 'status']);
        });

        Schema::table('logistics_documents', function (Blueprint $table) {
            $table->dropIndex(['document_type', 'status']);
        });
    }
};
