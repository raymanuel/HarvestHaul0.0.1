<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── CRITICAL: harvests hot-path indexes ──
        Schema::table('harvests', function (Blueprint $table) {
            // Buyer crop board: whereIn(status) + whereIn(visibility) + where(remaining_quantity_kg > 0)
            $table->index(['status', 'visibility', 'remaining_quantity_kg']);
            // Farmer dashboard: where(user_id) + where(status)
            $table->index(['user_id', 'status']);
            // ->latest() ordering on every harvest list page
            $table->index('created_at');
        });

        // ── CRITICAL: negotiations hot-path indexes ──
        Schema::table('negotiations', function (Blueprint $table) {
            // Harvest model accessors: where(harvest_id) + where(status='COMPLETED') — called per row
            $table->index(['harvest_id', 'status']);
            // Buyer dashboard: where(buyer_id) + whereIn(status)
            $table->index(['buyer_id', 'status']);
            // Farmer incoming negotiations
            $table->index(['farmer_id', 'status']);
            // listJson() ordering
            $table->index('last_activity_at');
        });

        // ── CRITICAL: pooling_jobs additional indexes ──
        Schema::table('pooling_jobs', function (Blueprint $table) {
            // Filtered alone by status in many queries (without logistics_profile_id)
            $table->index('status');
            // Buyer dashboard/tracking: where(buyer_id) + whereIn(status)
            $table->index(['buyer_id', 'status']);
            // Driver dashboard: where(driver_id) + whereIn(status)
            $table->index(['driver_id', 'status']);
        });

        // ── CRITICAL: pooling_job_harvests reverse pivot lookup ──
        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            // "Which pooling jobs contain this harvest?" — ResourcePoolingService conflict checks
            $table->index('harvest_id');
        });

        // ── HIGH: users role filtering ──
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
            $table->index(['role', 'status']);
        });

        // ── HIGH: driver_profiles dispatch index ──
        Schema::table('driver_profiles', function (Blueprint $table) {
            // Nearest driver dispatch: where(partner_id) + where(employment_status)
            $table->index(['partner_id', 'employment_status']);
        });

        // ── HIGH: trucks availability index ──
        Schema::table('trucks', function (Blueprint $table) {
            // Truck availability for dispatch: where(logistics_profile_id) + where(status)
            $table->index(['logistics_profile_id', 'status']);
        });

        // ── HIGH: logistics_profiles filtering ──
        Schema::table('logistics_profiles', function (Blueprint $table) {
            $table->index('logistics_type');
            $table->index('is_verified');
        });

        // ── HIGH: farmer_profiles filtering ──
        Schema::table('farmer_profiles', function (Blueprint $table) {
            $table->index('is_verified');
            $table->index(['affiliation_type', 'cooperative_id']);
        });

        // ── MEDIUM: tracking_records rate-limiting index ──
        Schema::table('tracking_records', function (Blueprint $table) {
            // Rate limiting: where(driver_id) + where(posted_at) — runs every 15s per driver
            $table->index(['driver_id', 'posted_at']);
        });

        // ── MEDIUM: invoices filtering ──
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['logistics_profile_id', 'status']);
            $table->index('status');
        });

        // ── MEDIUM: fuel_logs time-series index ──
        Schema::table('fuel_logs', function (Blueprint $table) {
            $table->index(['truck_id', 'created_at']);
        });

        // ── MEDIUM: document status indexes for admin pending lists ──
        Schema::table('farmer_documents', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });

        Schema::table('logistics_documents', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });

        // ── MEDIUM: negotiation_messages chronological listing ──
        Schema::table('negotiation_messages', function (Blueprint $table) {
            $table->index(['negotiation_id', 'created_at']);
        });

        // ── LOW: destinations active listing ──
        Schema::table('destinations', function (Blueprint $table) {
            $table->index(['is_active', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->dropIndex(['status', 'visibility', 'remaining_quantity_kg']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex('created_at');
        });

        Schema::table('negotiations', function (Blueprint $table) {
            $table->dropIndex(['harvest_id', 'status']);
            $table->dropIndex(['buyer_id', 'status']);
            $table->dropIndex(['farmer_id', 'status']);
            $table->dropIndex('last_activity_at');
        });

        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropIndex('status');
            $table->dropIndex(['buyer_id', 'status']);
            $table->dropIndex(['driver_id', 'status']);
        });

        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->dropIndex('harvest_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('role');
            $table->dropIndex(['role', 'status']);
        });

        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->dropIndex(['partner_id', 'employment_status']);
        });

        Schema::table('trucks', function (Blueprint $table) {
            $table->dropIndex(['logistics_profile_id', 'status']);
        });

        Schema::table('logistics_profiles', function (Blueprint $table) {
            $table->dropIndex('logistics_type');
            $table->dropIndex('is_verified');
        });

        Schema::table('farmer_profiles', function (Blueprint $table) {
            $table->dropIndex('is_verified');
            $table->dropIndex(['affiliation_type', 'cooperative_id']);
        });

        Schema::table('tracking_records', function (Blueprint $table) {
            $table->dropIndex(['driver_id', 'posted_at']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['logistics_profile_id', 'status']);
            $table->dropIndex('status');
        });

        Schema::table('fuel_logs', function (Blueprint $table) {
            $table->dropIndex(['truck_id', 'created_at']);
        });

        Schema::table('farmer_documents', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('logistics_documents', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('negotiation_messages', function (Blueprint $table) {
            $table->dropIndex(['negotiation_id', 'created_at']);
        });

        Schema::table('destinations', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'type']);
        });
    }
};
