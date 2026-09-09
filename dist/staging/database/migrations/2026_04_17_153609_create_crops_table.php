<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crops', function (Blueprint $table) {
            $table->id();

            $table->foreignId('crop_category_id')
                ->constrained('crop_categories')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('scientific_name')->nullable();
            $table->text('handling_notes')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['crop_category_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crops');
    }
};
