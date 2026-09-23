<?php

namespace App\Services\Matching;

use App\Models\BuyerProfile;
use App\Models\CropGrade;
use App\Services\Routing\HaversineService;
use Illuminate\Support\Collection;

/**
 * Ranks crop listings for a buyer by distance, quantity fit, and grade.
 * Advisory only — scores order the browse page, the buyer still chooses.
 * A component with missing input (no buyer location, no grade) is skipped
 * and the remaining weights are renormalized, so every listing still scores.
 */
class OrderMatchingService
{
    public function __construct(private HaversineService $haversine)
    {
    }

    /**
     * @param  Collection  $listings  CropAvailability models
     * @param  array{min_kg?: float|null}  $criteria
     */
    public function rank(Collection $listings, ?BuyerProfile $buyerProfile, array $criteria = []): Collection
    {
        $weights = config('harvesthaul.matching.weights');
        $maxDistanceKm = (float) config('harvesthaul.matching.max_useful_distance_km');
        $minKg = isset($criteria['min_kg']) ? (float) $criteria['min_kg'] : null;

        $gradeOrder = CropGrade::active()->pluck('sort_order', 'id');
        $maxGradeOrder = $gradeOrder->isNotEmpty() ? $gradeOrder->max() : null;
        $minGradeOrder = $gradeOrder->isNotEmpty() ? $gradeOrder->min() : null;

        $maxRemainingKg = $listings->max(fn ($listing) => $listing->remaining_kg) ?: 0.0;

        $hasBuyerLocation = $buyerProfile && $buyerProfile->latitude !== null && $buyerProfile->longitude !== null;

        return $listings->map(function ($listing) use (
            $weights, $maxDistanceKm, $minKg, $gradeOrder, $maxGradeOrder, $minGradeOrder,
            $maxRemainingKg, $buyerProfile, $hasBuyerLocation
        ) {
            $components = [];
            $reasons = [];
            $distanceKm = null;

            if ($hasBuyerLocation && $listing->cooperative?->latitude !== null && $listing->cooperative?->longitude !== null) {
                $distanceKm = $this->haversine->distanceKm(
                    (float) $buyerProfile->latitude,
                    (float) $buyerProfile->longitude,
                    (float) $listing->cooperative->latitude,
                    (float) $listing->cooperative->longitude,
                );
                $components['distance'] = max(0.0, min(1.0, 1 - ($distanceKm / $maxDistanceKm)));
                $reasons[] = $this->formatDistanceReason($distanceKm);
            }

            $remainingKg = $listing->remaining_kg;
            if ($minKg !== null && $minKg > 0) {
                $components['quantity'] = min(1.0, $remainingKg / $minKg);
                if ($remainingKg >= $minKg) {
                    $reasons[] = 'Covers your '.number_format($minKg, 0).' kg in one order';
                }
            } elseif ($maxRemainingKg > 0) {
                $components['quantity'] = $remainingKg / $maxRemainingKg;
            }

            if ($listing->crop_grade_id && $gradeOrder->has($listing->crop_grade_id) && $maxGradeOrder !== $minGradeOrder) {
                $order = $gradeOrder->get($listing->crop_grade_id);
                $components['grade'] = 1 - (($order - $minGradeOrder) / ($maxGradeOrder - $minGradeOrder));
                if ($listing->cropGrade) {
                    $reasons[] = 'Grade '.$listing->cropGrade->name;
                }
            } elseif ($listing->crop_grade_id && $listing->cropGrade) {
                $reasons[] = 'Grade '.$listing->cropGrade->name;
            }

            $score = $this->weightedScore($components, $weights);

            $listing->match_score = (int) round($score * 100);
            $listing->match_label = $this->labelFor($listing->match_score);
            $listing->match_reasons = array_slice($reasons, 0, 3);
            $listing->distance_km = $distanceKm;

            return $listing;
        })->sortByDesc('match_score')->values();
    }

    private function weightedScore(array $components, array $weights): float
    {
        if (empty($components)) {
            return 0.5;
        }

        $totalWeight = 0.0;
        $weightedSum = 0.0;

        foreach ($components as $key => $value) {
            $weight = $weights[$key] ?? 0;
            $weightedSum += $value * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0.5;
    }

    private function labelFor(int $score): string
    {
        $bands = config('harvesthaul.matching.bands');

        if ($score >= $bands['best']) {
            return 'Best match';
        }

        if ($score >= $bands['good']) {
            return 'Good match';
        }

        return 'Fair match';
    }

    private function formatDistanceReason(float $km): string
    {
        return number_format($km, $km < 10 ? 1 : 0).' km away';
    }
}
