<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PoolingJobHarvest extends Pivot
{
    protected $table = 'pooling_job_harvests';

    protected $casts = [
        'arrived_at'          => 'datetime',
        'loaded_at'           => 'datetime',
        'delivered_at'        => 'datetime',
        'buyer_confirmed_at'  => 'datetime',
    ];

    private function parseAttr(string $key): ?Carbon
    {
        $val = $this->attributes[$key] ?? null;
        if (!$val) return null;
        if ($val instanceof Carbon) return $val;
        return Carbon::parse($val);
    }

    public function getStopDurationAttribute(): ?array
    {
        $arrived   = $this->parseAttr('arrived_at');
        $loaded    = $this->parseAttr('loaded_at');
        $delivered = $this->parseAttr('delivered_at');
        $created   = $this->parseAttr('created_at');

        return [
            'travel_to_farm' => $arrived && $created
                ? (int) abs($arrived->diffInMinutes($created)) : null,
            'loading_dock' => $loaded && $arrived
                ? (int) abs($loaded->diffInMinutes($arrived)) : null,
            'delivery_run' => $delivered && $loaded
                ? (int) abs($delivered->diffInMinutes($loaded)) : null,
            'total_stop' => $delivered && $arrived
                ? (int) abs($delivered->diffInMinutes($arrived)) : null,
        ];
    }

    public function getStopDurationHumanAttribute(): ?array
    {
        $durations = $this->getStopDurationAttribute();
        if (!$durations) return null;

        $humanized = [];
        foreach ($durations as $key => $minutes) {
            if ($minutes === null) {
                $humanized[$key] = null;
            } elseif ($minutes < 1) {
                $humanized[$key] = '<1 min';
            } elseif ($minutes < 60) {
                $humanized[$key] = $minutes . ' min';
            } else {
                $h = intdiv($minutes, 60);
                $m = $minutes % 60;
                $humanized[$key] = $m > 0 ? "{$h}h {$m}m" : "{$h}h";
            }
        }
        return $humanized;
    }
}
