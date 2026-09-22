<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cooperatives', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->enum('type', ['primary', 'secondary', 'other'])->default('primary');
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->string('street_address')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('official_email')->nullable();
            $table->year('year_established')->nullable();
            $table->text('business_activities')->nullable();

            $table->string('cda_registration_number')->nullable();
            $table->date('registration_date')->nullable();
            $table->string('cert_document_path')->nullable();
            $table->string('articles_document_path')->nullable();
            $table->string('bylaws_document_path')->nullable();

            $table->string('rep_name')->nullable();
            $table->string('rep_position')->nullable();
            $table->string('rep_contact')->nullable();
            $table->string('rep_email')->nullable();
            $table->string('rep_id_type')->nullable();
            $table->string('rep_id_number')->nullable();
            $table->string('rep_id_document_path')->nullable();
            $table->string('rep_authorization_document_path')->nullable();

            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'suspended'])->default('pending')->index();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();

            $table->foreignId('coop_admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('latitude', 20)->nullable();
            $table->string('longitude', 20)->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cooperatives');
    }
};