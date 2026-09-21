<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receiving_records', function (Blueprint $table) {
            $table->decimal('facility_received_weight_kg', 10, 2)->nullable()->after('cancellation_reason');
            $table->timestamp('facility_verified_at')->nullable()->after('facility_received_weight_kg');
            $table->foreignId('facility_verified_by')->nullable()->after('facility_verified_at')->constrained('users')->nullOnDelete();
            $table->decimal('variance_kg', 10, 2)->nullable()->after('facility_verified_by');
            $table->string('variance_status', 16)->nullable()->after('variance_kg');
            $table->text('variance_notes')->nullable()->after('variance_status');
        });
    }

    public function down(): void
    {
        Schema::table('receiving_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facility_verified_by');
            $table->dropColumn(['facility_received_weight_kg', 'facility_verified_at', 'variance_kg', 'variance_status', 'variance_notes']);
        });
    }
};
