<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\CropVariety;
use Illuminate\Support\Facades\DB;

class CropResolverService
{
    /**
     * Find or create a crop by name (case-insensitive).
     * Creates with status='active' and null baseline if not found.
     */
    public function resolveCrop(string $name, int $categoryId): Crop
    {
        $normalized = $this->normalize($name);

        return DB::transaction(function () use ($normalized, $categoryId, $name) {
            $crop = Crop::where('crop_category_id', $categoryId)
                ->whereRaw('LOWER(name) = ?', [$normalized])
                ->first();

            if (!$crop) {
                try {
                    $crop = Crop::create([
                        'crop_category_id' => $categoryId,
                        'name' => $this->titleCase($name),
                        'status' => 'active',
                        'baseline_price_per_kg' => null,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Race condition: another request created the same crop — re-fetch
                    $crop = Crop::where('crop_category_id', $categoryId)
                        ->whereRaw('LOWER(name) = ?', [$normalized])
                        ->firstOrFail();
                }
            }

            return $crop;
        });
    }

    /**
     * Find or create a variety under a crop (case-insensitive).
     * Creates with status='active' and null price if not found.
     */
    public function resolveVariety(Crop $crop, string $name): CropVariety
    {
        $normalized = $this->normalize($name);

        return DB::transaction(function () use ($crop, $normalized, $name) {
            $variety = CropVariety::where('crop_id', $crop->id)
                ->whereRaw('LOWER(name) = ?', [$normalized])
                ->first();

            if (!$variety) {
                try {
                    $variety = CropVariety::create([
                        'crop_id' => $crop->id,
                        'name' => $this->titleCase($name),
                        'status' => 'active',
                        'price_per_kg' => 0,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    $variety = CropVariety::where('crop_id', $crop->id)
                        ->whereRaw('LOWER(name) = ?', [$normalized])
                        ->firstOrFail();
                }
            }

            return $variety;
        });
    }

    /**
     * Normalize a name for comparison: trim + lowercase.
     */
    private function normalize(string $value): string
    {
        return strtolower(trim($value));
    }

    /**
     * Title-case a name for display, protecting already all-caps words (acronyms).
     * "apple" -> "Apple", "dragon fruit" -> "Dragon Fruit", "GM Rice" -> "GM Rice".
     */
    private function titleCase(string $value): string
    {
        $value = trim($value);

        return implode(' ', array_map(function ($word) {
            if (mb_strlen($word) >= 2 && mb_strtoupper($word) === $word) {
                return $word;
            }

            return mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
        }, preg_split('/\s+/', $value)));
    }
}
