<?php

namespace App\Services;

/**
 * ─────────────────────────────────────────────────────────────
 * SERVICE: HaulingRateCalculator
 * ─────────────────────────────────────────────────────────────
 * Turns a road distance (OSRM, in km) + payload (kg) + terrain into
 * an ADVISORY suggested hauling rate (₱/kg) for a route.
 *
 * This is a fairness / sanity aid only. It is never billed directly.
 * Billing stays rate × kg, where the per-farmer hauling_rate_per_kg
 * agreed in the negotiation chat is the source of truth (see the
 * "project" knowledge block in AGENTS.md for the billing model).
 *
 * TERRAIN IN PLAIN WORDS:
 *   - flat        = open, level roads — the baseline
 *   - rolling     = gentle hills — more fuel up-and-down hills
 *   - mountainous = steep climbs — noticeably more fuel & wear
 *
 * SOURCES (see config/harvesthaul.php -> hauling.suggestion_sources):
 *   - Fuel price: DOE Philippines Oil Price Watch
 *   - Light-truck economy ≈ 7.4 km/L laden: ICCT studies
 *   - Terrain cost ratios: World Bank / FHWA road cost literature
 * ─────────────────────────────────────────────────────────────
 */
class HaulingRateCalculator
{
    /**
     * Suggested per-kg rate for a trip.
     *
     * costPerKm = fuel(L/km × ₱/L) + maintenance(₱/km) + driver(₱/km)
     * tripCost  = costPerKm × roadKm × terrainMult + base trip fee
     * ratePerKg = tripCost / totalKg
     *
     * @param float  $roadKm   Total road distance of the trip (km)
     * @param float  $totalKg  Cargo carried on the trip (kg)
     * @param string $terrain  flat | rolling | mountainous
     */
    public function suggest(float $roadKm, float $totalKg, string $terrain = 'flat'): array
    {
        $cfg = config('harvesthaul.hauling');

        $mult = (float) ($cfg['terrain_multipliers'][$terrain] ?? $cfg['terrain_multipliers']['flat']);
        $costPerKm = ((float) $cfg['fuel_liters_per_km'] * (float) $cfg['fuel_price_per_liter'])
            + (float) $cfg['maintenance_cost_per_km']
            + (float) $cfg['driver_cost_per_km'];

        $tripCost = ($costPerKm * $roadKm * $mult) + (float) $cfg['base_trip_fee'];

        return [
            'rate_per_kg' => $totalKg > 0 ? round($tripCost / $totalKg, 2) : null,
            'trip_cost'   => round($tripCost, 2),
            'cost_per_km' => round($costPerKm * $mult, 2),
            'road_km'     => round($roadKm, 2),
            'terrain'     => $terrain,
            'label'       => (string) ($cfg['suggestion_basis'] ?? 'road-cost estimate'),
        ];
    }

    /**
     * Sanity check: how a quoted rate compares to the road-cost suggestion.
     *
     * Returns null when nothing needs flagging (rate is in a healthy range).
     * Otherwise a warning-shaped message (rendered as an amber banner).
     *
     * @param float|null $usedRatePerKg      Rate actually quoted / agreed (₱/kg)
     * @param float|null $suggestedPerKg     Road-cost suggestion (₱/kg)
     * @param float|null $loadRatio          Share of truck capacity used (0-1);
     *                                       when light, the fair per-kg rate is higher
     */
    public static function sanity(?float $usedRatePerKg, ?float $suggestedPerKg, ?float $loadRatio = null): ?array
    {
        if ($suggestedPerKg === null || $suggestedPerKg <= 0) {
            return null;
        }

        $sanityCfg  = config('harvesthaul.hauling.sanity', []);
        $highRatio  = (float) ($sanityCfg['high_ratio'] ?? 1.75);
        $lowRatio   = (float) ($sanityCfg['low_ratio'] ?? 0.55);
        $lightRatio = (float) ($sanityCfg['light_load_warning_ratio'] ?? 0.4);

        $loadNote = null;
        if ($loadRatio !== null && $loadRatio < $lightRatio) {
            $loadNote = ' The truck is loaded light (about ' . round($loadRatio * 100) . '% of capacity), '
                . 'so a fair rate per kg is higher than when the truck is full.';
        }

        $sug = '₱' . number_format($suggestedPerKg, 2) . '/kg';

        if ($usedRatePerKg === null || $usedRatePerKg <= 0) {
            return [
                'level'   => 'warning',
                'message' => 'No hauling rate was set, so this route uses the distance fallback. '
                    . 'A fair road-cost rate for this trip is around ' . $sug
                    . '. Set a rate near that suggestion, or keep the fallback.' . ($loadNote ?? ''),
            ];
        }

        $ratio = $usedRatePerKg / $suggestedPerKg;

        if ($ratio > $highRatio) {
            return [
                'level'   => 'warning',
                'message' => 'The quoted rate (₱' . number_format($usedRatePerKg, 2) . '/kg) is '
                    . round($ratio * 100) . '% of the road-cost suggestion (' . $sug
                    . '). A premium this large is fair only if it covers extra services '
                    . 'beyond plain hauling (waiting time at farms, extra handling, '
                    . 'insurance, long detours).' . ($loadNote ?? ''),
            ];
        }

        if ($ratio < $lowRatio) {
            return [
                'level'   => 'warning',
                'message' => 'The quoted rate (₱' . number_format($usedRatePerKg, 2) . '/kg) is only '
                    . round($ratio * 100) . '% of the road-cost suggestion (' . $sug
                    . '). This trip may run at a loss once fuel, driver and maintenance '
                    . 'are paid for — consider raising the rate.' . ($loadNote ?? ''),
            ];
        }

        return null;
    }
}