<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── users.phone: contact number on the account itself ──
        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone')->nullable()->after('email');
            });
        }

        // ── users.cooperative_id: now points at cooperatives ──
        $this->dropForeignKey('users', 'cooperative_id');
        if (Schema::hasColumn('users', 'cooperative_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('cooperative_id');
            });
        }
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cooperative_id')->nullable()->after('affiliation_type')->constrained('cooperatives')->nullOnDelete();
        });

        // ── farmer_profiles.cooperative_id: point at cooperatives ──
        if (Schema::hasTable('farmer_profiles') && Schema::hasColumn('farmer_profiles', 'cooperative_id')) {
            $this->dropForeignKey('farmer_profiles', 'cooperative_id');
            Schema::table('farmer_profiles', function (Blueprint $table) {
                $table->foreign('cooperative_id')->references('id')->on('cooperatives')->nullOnDelete();
            });
        }

        // ── driver_profiles.partner_id → cooperative_id (delivery personnel) ──
        if (Schema::hasTable('driver_profiles') && Schema::hasColumn('driver_profiles', 'partner_id')) {
            $this->dropForeignKey('driver_profiles', 'partner_id');
            Schema::table('driver_profiles', function (Blueprint $table) {
                $table->renameColumn('partner_id', 'cooperative_id');
            });
            Schema::table('driver_profiles', function (Blueprint $table) {
                $table->foreign('cooperative_id')->references('id')->on('cooperatives')->cascadeOnDelete();
            });
        }

        // ── trucks.logistics_profile_id → cooperative_id (coop fleet) ──
        if (Schema::hasTable('trucks') && Schema::hasColumn('trucks', 'logistics_profile_id')) {
            $this->dropForeignKey('trucks', 'logistics_profile_id');
            Schema::table('trucks', function (Blueprint $table) {
                $table->renameColumn('logistics_profile_id', 'cooperative_id');
            });
            Schema::table('trucks', function (Blueprint $table) {
                $table->foreign('cooperative_id')->references('id')->on('cooperatives')->cascadeOnDelete();
            });
        }

        // ── buyer_profiles: capture business identity for B2B ordering ──
        if (Schema::hasTable('buyer_profiles')) {
            Schema::table('buyer_profiles', function (Blueprint $table) {
                $table->string('business_name')->nullable()->after('user_id');
                $table->string('contact_person')->nullable()->after('business_name');
                $table->text('business_address')->nullable()->after('phone');
            });
        }

        // ── tracking_records: generic morph target, drop pooling FK ──
        if (Schema::hasTable('tracking_records') && Schema::hasColumn('tracking_records', 'pooling_job_id')) {
            $this->dropForeignKey('tracking_records', 'pooling_job_id');
            Schema::table('tracking_records', function (Blueprint $table) {
                $table->dropIndex(['pooling_job_id', 'posted_at']);
                $table->dropColumn('pooling_job_id');
            });
            Schema::table('tracking_records', function (Blueprint $table) {
                $table->string('job_type', 32)->nullable()->after('driver_id');
                $table->unsignedBigInteger('job_id')->nullable()->after('job_type');
                $table->index(['job_type', 'job_id', 'posted_at']);
            });
        }
    }

    public function down(): void
    {
        // Not restoring legacy relationships.
    }

    private function dropForeignKey(string $table, string $column): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropForeign([$column]);
            });
        } catch (\Throwable $e) {
            // Constraint may already be gone — ignore.
        }
    }
};