<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignKey('haul_jobs', 'haul_request_id');

        Schema::table('haul_jobs', function (Blueprint $table) {
            $table->foreignId('haul_request_id')->nullable()->change();
        });

        Schema::table('haul_jobs', function (Blueprint $table) {
            $table->foreign('haul_request_id')->references('id')->on('haul_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $this->dropForeignKey('haul_jobs', 'haul_request_id');

        DB::table('haul_jobs')->whereNull('haul_request_id')->delete();

        Schema::table('haul_jobs', function (Blueprint $table) {
            $table->foreignId('haul_request_id')->nullable(false)->change();
        });

        Schema::table('haul_jobs', function (Blueprint $table) {
            $table->foreign('haul_request_id')->references('id')->on('haul_requests')->cascadeOnDelete();
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
