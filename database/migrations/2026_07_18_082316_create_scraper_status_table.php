<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraper_status', function (Blueprint $table) {
            $table->id();
            $table->string('scraper_name');           // e.g. 'darfo12'
            $table->string('status');                  // 'success' | 'failed' | 'skipped'
            $table->string('source_date')->nullable(); // date of the data fetched
            $table->string('message')->nullable();     // error message or summary
            $table->integer('records_matched')->default(0);
            $table->integer('records_skipped')->default(0);
            $table->timestamps();

            $table->index('scraper_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraper_status');
    }
};
