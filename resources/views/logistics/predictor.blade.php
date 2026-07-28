{{--
    Logistics Fleet Predictor View
    
    PURPOSE:
    This view displays a decision-support panel to help logistics coordinators 
    determine whether their active truck fleet matches current harvest volume demands.
    
    HEURISTIC:
    Trucks Needed = Total Active Harvest Weight (kg) / Average Weight per Completed Job
    If no job history exists, it falls back to Average Truck Capacity.
--}}
<x-layout>
    <div class="w-full max-w-4xl mx-auto pb-12">

        {{-- Header --}}
        <header class="mb-8 pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">
                        Fleet Predictor
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Forecast how many trucks you'll need based on active harvest load and historical job data
                    </p>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-[#1F4D25] dark:text-[#1F4D25] bg-[#1F4D25]/10 dark:bg-[#1F4D25]/10 px-3 py-1.5 rounded-lg border border-[#1F4D25]/10 dark:border-[#1F4D25]/20 self-start">
                    Fleet Predictor
                </span>
            </div>
        </header>

        {{-- Forecast Hero --}}
        @php
            $surplusColor = match(true) {
                $surplusShortage > 0  => 'text-[#3A7D44] dark:text-[#3A7D44]',
                $surplusShortage == 0 => 'text-amber-600 dark:text-amber-400',
                default               => 'text-rose-600 dark:text-rose-400',
            };
            $forecastLabel = match(true) {
                $trucksNeeded === 0   => 'No active load',
                $surplusShortage > 0  => 'Fleet Sufficient',
                $surplusShortage == 0 => 'Exact Capacity',
                default               => 'Fleet Shortage',
            };
        @endphp

        <div class="bg-gradient-to-br from-slate-800 to-slate-900 dark:from-slate-900 dark:to-slate-950 border border-slate-700/80 rounded-2xl p-6 sm:p-8 shadow-lg mb-8 relative overflow-hidden">
            {{-- Decorative bg --}}
            <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: radial-gradient(circle at 80% 50%, #059669 0%, transparent 60%)"></div>

            <div class="relative grid grid-cols-1 sm:grid-cols-3 gap-6 items-center">
                {{-- Trucks needed --}}
                <div class="text-center sm:text-left">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Trucks Needed</p>
                    <p class="text-5xl font-black text-white leading-none">{{ $trucksNeeded }}</p>
                    <p class="text-xs text-slate-400 font-semibold mt-2">based on {{ number_format($activeHarvestsKg, 1) }} kg active load</p>
                </div>

                {{-- Divider arrow --}}
                <div class="hidden sm:flex items-center justify-center">
                    <div class="flex flex-col items-center gap-1">
                        <div class="w-12 h-0.5 bg-slate-700"></div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">vs</p>
                        <div class="w-12 h-0.5 bg-slate-700"></div>
                    </div>
                </div>

                {{-- Available --}}
                <div class="text-center sm:text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Trucks Available</p>
                    <p class="text-5xl font-black text-white leading-none">{{ $availableTrucks }}</p>
                    <p class="text-xs text-slate-400 font-semibold mt-2">of {{ $totalTrucks }} total fleet units</p>
                </div>
            </div>

            {{-- Forecast verdict --}}
            <div class="mt-6 pt-5 border-t border-slate-700/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Forecast</p>
                    <p class="text-lg font-extrabold {{ $surplusColor }}">
                        {{ $forecastLabel }}
                        @if($surplusShortage !== 0 && $trucksNeeded > 0)
                            —
                            @if($surplusShortage > 0)
                                {{ $surplusShortage }} truck{{ $surplusShortage > 1 ? 's' : '' }} to spare
                            @else
                                {{ abs($surplusShortage) }} truck{{ abs($surplusShortage) > 1 ? 's' : '' }} short
                            @endif
                        @endif
                    </p>
                </div>
                @if($avgKgPerJob)
                    <div class="text-left sm:text-right">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Avg Load / Job</p>
                        <p class="text-sm font-bold text-slate-300 mt-0.5">{{ number_format($avgKgPerJob, 1) }} kg</p>
                    </div>
                @else
                    <p class="text-xs text-slate-500 italic">No completed job history yet — using avg truck capacity</p>
                @endif
            </div>
        </div>

        {{-- Key Metrics Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Active Harvest Load</p>
                <p class="text-xl font-bold text-slate-800 dark:text-slate-200">{{ number_format($activeHarvestsKg, 0) }}<span class="text-sm font-semibold text-slate-400 ml-1">kg</span></p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Completed Jobs</p>
                <p class="text-xl font-bold text-slate-800 dark:text-slate-200">{{ $completedJobs->count() }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Avg Truck Capacity</p>
                <p class="text-xl font-bold text-slate-800 dark:text-slate-200">{{ number_format($avgTruckCap, 0) }}<span class="text-sm font-semibold text-slate-400 ml-1">kg</span></p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Fleet Utilization</p>
                @php
                    $utilPct = $totalTrucks > 0 ? round((($totalTrucks - $availableTrucks) / $totalTrucks) * 100) : 0;
                @endphp
                <p class="text-xl font-bold text-slate-800 dark:text-slate-200">{{ $utilPct }}<span class="text-sm font-semibold text-slate-400 ml-0.5">%</span></p>
            </div>
        </div>

        {{-- Recent Job History --}}
        @if($recentJobs->isNotEmpty())
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/60">
                <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 heading-font">Recent Job History</h2>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-700/60">
                @foreach($recentJobs as $job)
                    @php
                        $statusColor = match($job['status']) {
                            'completed'   => 'text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10',
                            'in_progress' => 'text-[#1F4D25] dark:text-[#1F4D25] bg-[#1F4D25]/10 dark:bg-[#1F4D25]/10',
                            'confirmed'   => 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20',
                            default       => 'text-slate-500 bg-slate-50 dark:bg-slate-900/30',
                        };
                    @endphp
                    <div class="flex items-center justify-between px-6 py-3.5 hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-[10px] text-slate-400 w-8">#{{ $job['id'] }}</span>
                            <div>
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $job['truck'] }}</p>
                                <p class="text-[10px] text-slate-400 font-semibold">{{ $job['farms'] }} {{ Str::plural('farm', $job['farms']) }} &middot; {{ number_format($job['total_kg'], 1) }} kg</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($job['completed'])
                                <span class="text-[10px] text-slate-400 font-semibold">{{ $job['completed'] }}</span>
                            @endif
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md {{ $statusColor }} capitalize">
                                {{ str_replace('_', ' ', $job['status']) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Disclaimer --}}
        <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 rounded-xl px-5 py-4 text-xs text-slate-400 dark:text-slate-500 font-semibold">
            <svg class="w-4 h-4 inline -mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg> Fleet forecast is a heuristic estimate using average kg per completed job. Actual requirements depend on route distance, pickup density, and cargo type.
        </div>
    </div>
</x-layout>
