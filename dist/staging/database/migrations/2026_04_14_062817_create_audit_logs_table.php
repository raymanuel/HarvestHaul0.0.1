<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('action');           // e.g. "verified_farmer"
            $table->string('target_type');      // e.g. "farmer", "logistics_partner"
            $table->foreignId('target_id')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();  // e.g. "Approved farmer profile"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
