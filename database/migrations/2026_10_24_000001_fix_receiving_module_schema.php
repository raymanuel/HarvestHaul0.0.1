<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('receiving_records', 'haul_request_id')) {
            Schema::table('receiving_records', function (Blueprint $table) {
                $table->foreignId('haul_request_id')->nullable()->after('haul_job_id')
                    ->constrained('haul_requests')->nullOnDelete();
            });
        }

        Schema::table('receiving_records', function (Blueprint $table) {
            $table->decimal('buying_price_per_kg', 10, 2)->nullable()->change();
            $table->decimal('total_amount', 12, 2)->nullable()->change();
        });

        // crop_grade_id's existing FK is ON DELETE SET NULL, which requires a
        // nullable column — drop and re-add as ON DELETE RESTRICT so the
        // column can become required (spec 11.4/12: grade must be selected).
        $this->dropForeignKey('receiving_records', 'crop_grade_id');
        Schema::table('receiving_records', function (Blueprint $table) {
            $table->foreignId('crop_grade_id')->nullable(false)->change();
        });
        Schema::table('receiving_records', function (Blueprint $table) {
            $table->foreign('crop_grade_id')->references('id')->on('crop_grades')->restrictOnDelete();
        });

        Schema::table('crop_availabilities', function (Blueprint $table) {
            $table->decimal('selling_price_per_kg', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('receiving_records', function (Blueprint $table) {
            if (Schema::hasColumn('receiving_records', 'haul_request_id')) {
                $table->dropForeign(['haul_request_id']);
                $table->dropColumn('haul_request_id');
            }
            $table->decimal('buying_price_per_kg', 10, 2)->nullable(false)->change();
            $table->decimal('total_amount', 12, 2)->nullable(false)->change();
        });

        $this->dropForeignKey('receiving_records', 'crop_grade_id');
        Schema::table('receiving_records', function (Blueprint $table) {
            $table->foreignId('crop_grade_id')->nullable()->change();
        });
        Schema::table('receiving_records', function (Blueprint $table) {
            $table->foreign('crop_grade_id')->references('id')->on('crop_grades')->nullOnDelete();
        });

        Schema::table('crop_availabilities', function (Blueprint $table) {
            $table->decimal('selling_price_per_kg', 10, 2)->nullable(false)->change();
        });
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
