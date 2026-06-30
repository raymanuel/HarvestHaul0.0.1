{{--
    Farmer Yield Predictor View
    
    PURPOSE:
    This view presents a predictive dashboard for farmers. It estimates the next 
    harvest date for each crop type based on the historical interval between 
    consecutive harvests.
    
    HEURISTIC:
    Next Harvest Date = Last Harvest Date + (Sum of gaps in days between past harvests / Number of gaps)
--}}
<x-layout>
    <div class="w-full max-w-4xl mx-auto pb-12">

        {{-- Header --}}
        <header class="mb-8 pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">
                        Yield Predictor
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Estimated next harvest windows based on your historical planting cycles
                    </p>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-violet-700 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/20 px-3 py-1.5 rounded-lg border border-violet-500/10 dark:border-violet-500/20 self-start">
                    Yield Predictor
                </span>
            </div>
        </header>

        {{-- Overview Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Active Listings</p>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $activeCount }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Completed Hauls</p>
                <p class="text-2xl font-bold text-slate-800 dark:text-slate-200">{{ $completedCount }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm col-span-2 sm:col-span-1">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Crops Tracked</p>
                <p class="text-2xl font-bold text-slate-800 dark:text-slate-200">{{ $predictions->count() }}</p>
            </div>
        </div>

        @if($predictions->isEmpty())
            {{-- Empty State --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-12 text-center shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-violet-50 dark:bg-violet-950/20 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <p class="text-slate-600 dark:text-slate-400 font-bold text-sm heading-font">Not enough data yet</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 max-w-sm mx-auto leading-relaxed">
                    Predictions improve as you log more harvest dates. Post at least 2 harvests per crop type to generate cycle estimates.
                </p>
                <a href="{{ route('harvests.create') }}"
                   class="mt-5 inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Post New Harvest
                </a>
            </div>
        @else
            {{-- Prediction Cards --}}
            <div class="space-y-4">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Crop Cycle Predictions</h2>

                @foreach($predictions as $pred)
                    @php
                        $urgency = match(true) {
                            $pred['days_until'] !== null && $pred['days_until'] <= 7  => ['border-rose-300 dark:border-rose-700/50 bg-rose-50/30 dark:bg-rose-950/10', 'text-rose-600 dark:text-rose-400', '🔴 Harvest Soon'],
                            $pred['days_until'] !== null && $pred['days_until'] <= 21 => ['border-amber-300 dark:border-amber-700/50 bg-amber-50/30 dark:bg-amber-950/10', 'text-amber-600 dark:text-amber-400', '🟡 Coming Up'],
                            $pred['days_until'] !== null && $pred['days_until'] > 21  => ['border-slate-200/70 dark:border-slate-700/80 bg-white dark:bg-slate-800', 'text-emerald-600 dark:text-emerald-400', '🟢 On Track'],
                            default => ['border-slate-200/70 dark:border-slate-700/80 bg-white dark:bg-slate-800', 'text-slate-400', '⚪ Estimating...'],
                        };
                    @endphp
                    <div class="border {{ $urgency[0] }} rounded-2xl shadow-sm p-5 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 heading-font">
                                        🌾 {{ $pred['crop'] }}
                                    </h3>
                                    <span class="text-[10px] font-bold {{ $urgency[1] }} bg-white dark:bg-slate-800 border border-current/20 px-2 py-0.5 rounded-md">
                                        {{ $urgency[2] }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 dark:text-slate-500 font-semibold">
                                    {{ $pred['harvest_count'] }} {{ Str::plural('harvest', $pred['harvest_count']) }} logged &middot; Last: {{ $pred['last_harvest'] }}
                                </p>
                            </div>

                            {{-- Next Estimate Highlight --}}
                            @if($pred['next_estimate'])
                                <div class="text-left sm:text-right shrink-0">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Next Estimated Window</p>
                                    <p class="text-xl font-extrabold {{ $urgency[1] }} mt-0.5">{{ $pred['next_estimate'] }}</p>
                                    @if($pred['days_until'] !== null)
                                        <p class="text-xs font-bold text-slate-400 mt-0.5">
                                            {{ $pred['days_until'] > 0 ? 'in ' . $pred['days_until'] . ' days' : 'Today or overdue' }}
                                        </p>
                                    @endif
                                </div>
                            @else
                                <div class="text-left sm:text-right shrink-0">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Prediction</p>
                                    <p class="text-sm font-bold text-slate-400 italic mt-1">Need more data</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Log 2+ harvests to predict</p>
                                </div>
                            @endif
                        </div>

                        {{-- Stats row --}}
                        <div class="grid grid-cols-3 gap-3 mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Avg Cycle</p>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 mt-0.5">
                                    {{ $pred['avg_cycle_days'] ? $pred['avg_cycle_days'] . ' days' : '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Avg Yield</p>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 mt-0.5">{{ number_format($pred['avg_yield_kg'], 1) }} kg</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Yield</p>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300 mt-0.5">{{ number_format($pred['total_yield_kg'], 1) }} kg</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Disclaimer --}}
            <div class="mt-6 bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 rounded-xl px-5 py-4 text-xs text-slate-400 dark:text-slate-500 font-semibold">
                ℹ️ Predictions are heuristic estimates based on your harvest history. Actual timing may vary due to weather, soil conditions, and crop variety.
            </div>
        @endif
    </div>
</x-layout>
