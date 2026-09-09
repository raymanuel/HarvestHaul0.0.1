<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('document_type', [
                'dti_sec',
                'business_permit',
                'bir_cert',
                'mayors_permit',
            ]);
            $table->string('file_path');
            $table->string('original_filename');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('business_permit_match_confirmed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_documents');
    }
};
