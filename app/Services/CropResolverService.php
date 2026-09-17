<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\CropVariety;
use Illuminate\Support\Facades\DB;

class CropResolverService
{
    /**
     * Find or create a crop by name (case-insensitive).
     *
     * When no exact match exists, a close typo match against the existing
     * crop names is reused instead of storing the typo as a new crop. The
     * original typed name is written to $correctedFrom when that happens.
     * Creates with status='active' and null baseline if truly new.
     */
    public function resolveCrop(string $name, int $categoryId, ?string &$correctedFrom = null): Crop
    {
        $normalized = $this->normalize($name);
        $correctedFrom = null;

        return DB::transaction(function () use ($normalized, $categoryId, $name, &$correctedFrom) {
            $crop = Crop::where('crop_category_id', $categoryId)
                ->whereRaw('LOWER(name) = ?', [$normalized])
                ->first();

            if (!$crop) {
                $candidates = Crop::query()->pluck('name')->map(fn ($n) => $this->normalize($n))->all();
                $match = $this->closeMatch($normalized, $candidates);

                if ($match !== null) {
                    $crop = Crop::whereRaw('LOWER(name) = ?', [$match])->firstOrFail();
                    if (levenshtein($normalized, $match) > 0) {
                        $correctedFrom = $name;
                    }
                }
            }

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
     *
     * Same typo-correcting behavior as resolveCrop, but only against the
     * varieties of the given crop. Creates with status='active' and null
     * price if truly new.
     */
    public function resolveVariety(Crop $crop, string $name, ?string &$correctedFrom = null): CropVariety
    {
        $normalized = $this->normalize($name);
        $correctedFrom = null;

        return DB::transaction(function () use ($crop, $normalized, $name, &$correctedFrom) {
            $variety = CropVariety::where('crop_id', $crop->id)
                ->whereRaw('LOWER(name) = ?', [$normalized])
                ->first();

            if (!$variety) {
                $candidates = CropVariety::where('crop_id', $crop->id)
                    ->pluck('name')
                    ->map(fn ($n) => $this->normalize($n))
                    ->all();
                $match = $this->closeMatch($normalized, $candidates);

                if ($match !== null) {
                    $variety = CropVariety::where('crop_id', $crop->id)
                        ->whereRaw('LOWER(name) = ?', [$match])
                        ->firstOrFail();
                    if (levenshtein($normalized, $match) > 0) {
                        $correctedFrom = $name;
                    }
                }
            }

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
     * Return the single nearest candidate name within a small edit distance,
     * or null when none fits (or two candidates tie). Never matches at a
     * distance of zero; callers treat those as exact and skip correction.
     */
    private function closeMatch(string $normalized, array $candidates): ?string
    {
        if (strlen($normalized) < 3) {
            return null;
        }

        $threshold = strlen($normalized) <= 4 ? 1 : 2;
        $bestDist = null;
        $best = null;
        $ties = false;

        foreach ($candidates as $candidate) {
            $dist = levenshtein($normalized, $candidate);
            if ($bestDist === null || $dist < $bestDist) {
                $bestDist = $dist;
                $best = $candidate;
                $ties = false;
            } elseif ($dist === $bestDist) {
                $ties = true;
            }
        }

        if ($bestDist === null || $bestDist > $threshold || $ties) {
            return null;
        }

        return $best;
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
