<?php

namespace App\Console\Commands;

use App\Models\HaulJob;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Weather\WeatherService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Checks today's/near-term scheduled haul jobs against the Open-Meteo
 * forecast for their cooperative's depot, and notifies the cooperative's
 * admins once when severe weather is forecast. Advisory only — never
 * cancels or reschedules a trip; the coop admin decides.
 */
class DetectWeatherRisk extends Command
{
    protected $signature = 'haul:detect-weather-risk';

    protected $description = 'Flag scheduled haul jobs facing severe forecast weather and notify the cooperative once.';

    public function handle(WeatherService $weather): int
    {
        $jobs = HaulJob::query()
            ->whereIn('status', [HaulJob::STATUS_SCHEDULED, HaulJob::STATUS_PICKED_UP])
            ->whereNull('weather_alerted_at')
            ->whereDate('pickup_date', '>=', today())
            ->whereDate('pickup_date', '<=', today()->addDay())
            ->with('cooperative')
            ->get();

        $flagged = 0;

        foreach ($jobs as $job) {
            $cooperative = $job->cooperative;
            $lat = (float) ($cooperative?->latitude ?? 0);
            $lng = (float) ($cooperative?->longitude ?? 0);

            if (! $lat || ! $lng) {
                continue;
            }

            $forecast = $weather->forecastAt($lat, $lng, Carbon::parse($job->pickup_date));
            if (! $forecast || $weather->severity($forecast) !== 'severe') {
                continue;
            }

            $this->flag($job);
            $flagged++;
        }

        $this->info("Flagged {$flagged} trip(s) facing severe weather.");

        return self::SUCCESS;
    }

    private function flag(HaulJob $job): void
    {
        $admins = User::where('role', UserRole::COOP_ADMIN->value)
            ->where('cooperative_id', $job->cooperative_id)
            ->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id'  => $admin->id,
                'title'    => 'Severe weather forecast for a scheduled trip',
                'message'  => "Trip {$job->id} is scheduled for {$job->pickup_date->format('M d')} — severe weather is forecast. Consider rescheduling.",
                'link'     => $job->isDelivery() ? route('coop.outbound.show', $job) : route('coop.pickups.show', $job),
                'category' => 'haul',
            ]);
        }

        $job->update(['weather_alerted_at' => now()]);
    }
}
