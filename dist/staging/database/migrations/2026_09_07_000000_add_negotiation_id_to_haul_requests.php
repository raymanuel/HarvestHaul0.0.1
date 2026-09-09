<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links each haul request to the crop negotiation (deal) it belongs to.
     * Until now the two only met through the shared harvest; this is the direct
     * deal linkage used by the farmer's unified Deal Room.
     */
    public function up(): void
    {
        Schema::table('haul_requests', function (Blueprint $table) {
            $table->foreignId('negotiation_id')
                ->nullable()
                ->after('buyer_id')
                ->constrained('negotiations')
                ->nullOnDelete();
        });

        DB::table('haul_requests')
            ->orderBy('id')
            ->chunkById(200, function ($requests) {
                foreach ($requests as $request) {
                    $negotiationId = DB::table('negotiations')
                        ->where('harvest_id', $request->harvest_id)
                        ->whereIn('status', ['AGREED', 'COMPLETED'])
                        ->orderBy('id', 'desc')
                        ->value('id');

                    if ($negotiationId) {
                        DB::table('haul_requests')
                            ->where('id', $request->id)
                            ->update(['negotiation_id' => $negotiationId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('haul_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('negotiation_id');
        });
    }
};