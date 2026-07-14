<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =====================================================
        // 1. HARVEST MODULE — add negotiation lock, PH GPS bounds
        // =====================================================
        Schema::table('harvests', function (Blueprint $table) {
            $table->timestamp('negotiation_locked_at')->nullable()->after('packaging_type');
        });

        // =====================================================
        // 2. NEGOTIATION MODULE — add timeout tracking
        // =====================================================
        Schema::table('negotiations', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('status');
        });

        // =====================================================
        // 3. DRIVER HEARTBEAT — real-time GPS for nearest-driver
        // =====================================================
        Schema::create('driver_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('logistics_profile_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamp('reported_at');
            $table->timestamps();
            $table->index(['driver_id', 'reported_at']);
            $table->index('logistics_profile_id');
        });

        // =====================================================
        // 4. DRIVER SCHEDULES — rest/compliance tracking
        // =====================================================
        Schema::create('driver_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->date('work_date');
            $table->time('shift_start');
            $table->time('shift_end');
            $table->enum('status', ['scheduled', 'active', 'completed', 'missed'])->default('scheduled');
            $table->timestamps();
            $table->unique(['driver_id', 'work_date']);
        });

        // =====================================================
        // 5. WEATHER LOGS — history per route per waypoint
        // =====================================================
        Schema::create('weather_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pooling_job_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('condition')->nullable();
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('feels_like', 5, 2)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->decimal('wind_speed', 6, 2)->nullable();
            $table->decimal('wind_gust', 6, 2)->nullable();
            $table->integer('visibility')->nullable();
            $table->text('advisory')->nullable();
            $table->boolean('is_severe')->default(false);
            $table->string('forecast_json', 5000)->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
            $table->index('pooling_job_id');
            $table->index('checked_at');
        });

        // =====================================================
        // 6. INVOICE — add voiding and payment tracking
        // =====================================================
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('sent_at');
            $table->text('void_reason')->nullable()->after('voided_at');
            $table->date('due_at')->nullable()->after('void_reason');
            $table->timestamp('paid_at')->nullable()->after('due_at');
        });

        // =====================================================
        // 7. TRACKING RECORDS — add accuracy field
        // =====================================================
        Schema::table('tracking_records', function (Blueprint $table) {
            $table->decimal('accuracy_meters', 8, 2)->nullable()->after('bearing');
        });

        // =====================================================
        // 8. NOTIFICATIONS — add type/category and retention
        // =====================================================
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type')->nullable()->after('user_id');
            $table->string('category')->nullable()->after('type');
            $table->index(['user_id', 'read_at', 'created_at']);
        });

        // =====================================================
        // 9. POOLING JOBS — add negotiation rounds counter
        // =====================================================
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->unsignedSmallInteger('negotiation_rounds')->default(0)->after('notes');
            $table->timestamp('proposal_expires_at')->nullable()->after('negotiation_rounds');
        });

        // =====================================================
        // 10. POOLING JOB HARVESTS PIVOT — add time tracking
        // =====================================================
        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->timestamp('arrived_at')->nullable()->after('delivery_receipt_path');
            $table->timestamp('loaded_at')->nullable()->after('arrived_at');
            $table->timestamp('delivered_at')->nullable()->after('loaded_at');
        });

        // =====================================================
        // 11. DRIVER PROFILES — add license restrictions
        // =====================================================
        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->string('license_restriction')->nullable()->after('license_no');
            $table->time('last_shift_ended_at')->nullable()->after('last_assigned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_heartbeats');
        Schema::dropIfExists('driver_schedules');
        Schema::dropIfExists('weather_logs');

        Schema::table('harvests', fn(Blueprint $t) => $t->dropColumn('negotiation_locked_at'));
        Schema::table('negotiations', fn(Blueprint $t) => $t->dropColumn('last_activity_at'));
        Schema::table('invoices', fn(Blueprint $t) => $t->dropColumn(['voided_at', 'void_reason', 'due_at', 'paid_at']));
        Schema::table('tracking_records', fn(Blueprint $t) => $t->dropColumn('accuracy_meters'));
        Schema::table('notifications', fn(Blueprint $t) => $t->dropColumn(['type', 'category']));
        Schema::table('pooling_jobs', fn(Blueprint $t) => $t->dropColumn(['negotiation_rounds', 'proposal_expires_at']));
        Schema::table('pooling_job_harvests', fn(Blueprint $t) => $t->dropColumn(['arrived_at', 'loaded_at', 'delivered_at']));
        Schema::table('driver_profiles', fn(Blueprint $t) => $t->dropColumn(['license_restriction', 'last_shift_ended_at']));
    }
};
