<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooperatives', function (Blueprint $table) {
            $table->enum('status', ['pending', 'under_review', 'requires_revision', 'approved', 'rejected', 'suspended'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('cooperatives')->where('status', 'requires_revision')->update(['status' => 'pending']);

        Schema::table('cooperatives', function (Blueprint $table) {
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'suspended'])
                ->default('pending')
                ->change();
        });
    }
};
