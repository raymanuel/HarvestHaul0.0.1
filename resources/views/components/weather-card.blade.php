@props(['weather' => null])

@php
    $condition = $weather->condition ?? null;
    $desc = $weather->description ?? null;
    $temp = $weather->temperature ?? null;
    $wind = $weather->wind_speed ?? null;
    $humidity = $weather->humidity ?? null;
    $advisory = $weather->advisory ?? null;
    $severe = (bool) ($weather->is_severe ?? false);
    $checkedAt = $weather->checked_at ?? null;

    $fresh = false;
    if ($checkedAt) {
        $ageHours = \Carbon\Carbon::parse($checkedAt)->diffInHours(now(), false);
        $fresh = abs($ageHours) < 3;
    }
@endphp

<div {{ $attributes->merge(['class' => 'bg-surface-card dark:bg-surface-card-dark border border-slate-200/60 dark:border-dark-border rounded-2xl overflow-hidden flex flex-col']) }}>

    {{-- Header --}}
    <div class="px-6 pt-6 pb-4 border-b border-slate-100 dark:border-dark-border">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white heading-font">Weather Conditions</h2>
            <div class="flex items-center gap-2">
                @if($checkedAt)
                    <span class="text-[10px] font-bold {{ $fresh ? 'text-info-text bg-info-bg border-info-border' : 'text-warning-text bg-warning-bg border-warning-border' }} px-2 py-0.5 rounded border">
                        {{ \Carbon\Carbon::parse($checkedAt)->diffForHumans() }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if(!$weather)
        <div class="py-10 text-center flex-1 flex flex-col items-center justify-center">
            <p class="text-xs text-slate-500 dark:text-slate-400 font-semibold">No weather data recorded yet.</p>
            <p class="text-[10px] text-slate-500 dark:text-slate-600 mt-1">Weather checks run for active shipments throughout the day.</p>
        </div>
    @else
        @if($severe)
            <div class="mx-6 mt-4 px-3 py-2 rounded-xl bg-warning-bg dark:bg-warning-bg-dark border border-warning-border dark:border-warning-border-dark">
                <p class="text-[10px] font-bold text-warning-text dark:text-warning-text-dark">
                    {{ $advisory ?: 'Severe weather conditions.' }}
                </p>
            </div>
        @endif

        <div class="px-6 py-5 flex-1 flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center {{ $severe ? 'bg-warning-bg text-warning-text' : 'bg-brand/10 text-brand dark:text-brand-light' }}">
                    <x-icon name="{{ $condition && str_contains(strtolower($condition), 'cloud') ? 'cloud-sun' : ($condition && str_contains(strtolower($condition), 'rain') ? 'cloud-sun' : 'sun') }}" class="w-7 h-7" />
                </div>
                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-mono font-extrabold text-slate-900 dark:text-white">{{ $temp !== null ? round($temp) . '°' : '—' }}</span>
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Celsius</span>
                    </div>
                    <p class="text-[11px] font-bold text-slate-700 dark:text-slate-300 capitalize">{{ $condition ?: 'Unknown' }}{{ $desc ? ' — ' . $desc : '' }}</p>
                </div>
            </div>

            <div class="flex gap-6 text-right shrink-0">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-text-muted dark:text-text-dark-muted">Wind</p>
                    <p class="text-[12px] font-mono font-extrabold text-slate-800 dark:text-slate-200">{{ $wind !== null ? round($wind) . ' km/h' : '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-text-muted dark:text-text-dark-muted">Humidity</p>
                    <p class="text-[12px] font-mono font-extrabold text-slate-800 dark:text-slate-200">{{ $humidity !== null ? round($humidity) . '%' : '—' }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
