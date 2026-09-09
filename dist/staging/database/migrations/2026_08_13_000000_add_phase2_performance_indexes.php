<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── HIGH: haul_requests status filtering ──
        Schema::table('haul_requests', function (Blueprint $table) {
            $table->index('status');
            $table->index(['harvest_id', 'status']);
        });

        // ── HIGH: haul_intents status filtering ──
        Schema::table('haul_intents', function (Blueprint $table) {
            $table->index('status');
            $table->index(['haul_request_id', 'status']);
        });

        // ── MEDIUM: pooling_jobs ->latest() ordering ──
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });

        // ── MEDIUM: pooling_job_harvests stop status lookups ──
        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->index('status');
            $table->index(['pooling_job_id', 'status']);
        });

        // ── MEDIUM: audit_logs admin filtering + chronological listing ──
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('admin_id');
            $table->index('created_at');
        });

        // ── MEDIUM: negotiations chronological listing (extends existing 2-col composites) ──
        Schema::table('negotiations', function (Blueprint $table) {
            $table->index(['buyer_id', 'status', 'created_at']);
            $table->index(['farmer_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('haul_requests', function (Blueprint $table) {
            $table->dropIndex(['harvest_id', 'status']);
            $table->dropIndex('status');
        });

        Schema::table('haul_intents', function (Blueprint $table) {
            $table->dropIndex(['haul_request_id', 'status']);
            $table->dropIndex('status');
        });

        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->dropIndex(['pooling_job_id', 'status']);
            $table->dropIndex('status');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('admin_id');
            $table->dropIndex('created_at');
        });

        Schema::table('negotiations', function (Blueprint $table) {
            $table->dropIndex(['buyer_id', 'status', 'created_at']);
            $table->dropIndex(['farmer_id', 'status', 'created_at']);
        });
    }
};
