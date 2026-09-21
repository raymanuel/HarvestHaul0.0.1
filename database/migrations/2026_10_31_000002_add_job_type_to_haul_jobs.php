<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haul_jobs', function (Blueprint $table) {
            $table->string('job_type', 16)->default('pickup')->after('cooperative_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('haul_jobs', function (Blueprint $table) {
            $table->dropColumn('job_type');
        });
    }
};
