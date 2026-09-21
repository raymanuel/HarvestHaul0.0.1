<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_calculations', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32);
            $table->string('profile', 32);
            $table->string('kind', 16); // 'table' | 'route'
            // Hash of the exact ordered coordinate sequence sent to the
            // provider — a cache hit only ever reuses an identical request.
            $table->string('coordinates_hash', 64)->index();
            $table->json('result');
            // Nullable, not defaulted: MySQL strict mode rejects two
            // NOT-NULL timestamp columns unless one has an explicit
            // DEFAULT — the app always sets both explicitly on write.
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();

            $table->unique(['provider', 'profile', 'kind', 'coordinates_hash'], 'route_calculations_lookup_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_calculations');
    }
};
