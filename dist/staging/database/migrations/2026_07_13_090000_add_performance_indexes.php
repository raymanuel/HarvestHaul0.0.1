<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pooling_jobs — FK indexes already exist; add composite for list queries
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->index(['logistics_profile_id', 'status']);
        });

        // harvests — FK indexes + status + user_id already exist; add spatial composite
        Schema::table('harvests', function (Blueprint $table) {
            $table->index(['status', 'latitude', 'longitude']);
        });

        // negotiations — FK indexes already exist; add status
        Schema::table('negotiations', function (Blueprint $table) {
            $table->index('status');
        });

        // audit_logs — FK indexes already exist; add composite + action
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['target_type', 'target_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::table('pooling_jobs', function (Blueprint $table) {
            $table->dropIndex(['logistics_profile_id', 'status']);
        });

        Schema::table('harvests', function (Blueprint $table) {
            $table->dropIndex(['status', 'latitude', 'longitude']);
        });

        Schema::table('negotiations', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['target_type', 'target_id']);
            $table->dropIndex(['action']);
        });
    }
};
