<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Load photo + actual quantity on pivot (guarded: may already exist)
        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            if (!Schema::hasColumn('pooling_job_harvests', 'load_photo_path')) {
                $table->string('load_photo_path')->nullable()->after('delivery_receipt_path');
            }
            if (!Schema::hasColumn('pooling_job_harvests', 'actual_quantity_kg')) {
                $table->decimal('actual_quantity_kg', 10, 2)->nullable()->after('loaded_quantity_kg');
            }
            if (!Schema::hasColumn('pooling_job_harvests', 'farmer_qty_confirmed')) {
                $table->boolean('farmer_qty_confirmed')->default(false)->after('actual_quantity_kg');
            }
        });

        // End-of-trip odometer on pooling_jobs (guarded)
        Schema::table('pooling_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('pooling_jobs', 'end_odometer_reading')) {
                $table->decimal('end_odometer_reading', 10, 2)->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('pooling_jobs', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('confirmed_at');
            }
        });

        // Track last known odometer on trucks for fuel sufficiency checks (guarded)
        Schema::table('trucks', function (Blueprint $table) {
            if (!Schema::hasColumn('trucks', 'last_odometer_reading')) {
                $table->decimal('last_odometer_reading', 10, 2)->nullable()->after('status');
            }
        });

        // Mail tracking on notifications (guarded)
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'email_sent_at')) {
                $table->timestamp('email_sent_at')->nullable()->after('read_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pooling_job_harvests', function (Blueprint $table) {
            $table->dropColumn(['load_photo_path', 'actual_quantity_kg', 'farmer_qty_confirmed']);
        });
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropColumn(['end_odometer_reading', 'accepted_at']);
        });
        Schema::table('trucks', function (Blueprint $table) {
            $table->dropColumn('last_odometer_reading');
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('email_sent_at');
        });
    }
};
