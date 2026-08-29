<?php

namespace App\Console\Commands;

use App\Models\DriverProfile;
use App\Models\FarmerDocument;
use App\Models\Invoice;
use App\Models\LogisticsDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Moves sensitive uploads from the public disk to the private disk so they are
 * no longer served from /storage. Runs after deploying the private-disk changes.
 */
class MigratePrivateFiles extends Command
{
    protected $signature = 'files:migrate-private';

    protected $description = 'Move sensitive uploaded files (IDs, receipts, photos) from the public disk to the private disk';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');

        $paths = collect();

        FarmerDocument::query()->pluck('file_path')->each(fn ($p) => $paths->push($p));
        LogisticsDocument::query()->pluck('file_path')->each(fn ($p) => $paths->push($p));
        DriverProfile::query()->pluck('id_photo_path')->each(fn ($p) => $p && $paths->push($p));
        DriverProfile::query()->pluck('selfie_path')->each(fn ($p) => $p && $paths->push($p));
        DB::table('pooling_job_harvests')->pluck('receipt_path')->each(fn ($p) => $p && $paths->push($p));
        DB::table('pooling_job_harvests')->pluck('load_photo_path')->each(fn ($p) => $p && $paths->push($p));
        DB::table('pooling_job_harvests')->pluck('delivery_receipt_path')->each(fn ($p) => $p && $paths->push($p));
        Invoice::query()->pluck('pdf_path')->each(fn ($p) => $p && $paths->push($p));

        $paths = $paths->filter()->unique();

        $moved = 0;
        $missing = 0;

        foreach ($paths as $path) {
            if (!$public->exists($path)) {
                $missing++;
                continue;
            }

            if (!$private->exists($path)) {
                $stream = $public->readStream($path);
                $private->put($path, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $public->delete($path);
            $moved++;
        }

        $this->info("Migrated {$moved} file(s) to the private disk. {$missing} referenced file(s) not found on public disk.");

        return self::SUCCESS;
    }
}
