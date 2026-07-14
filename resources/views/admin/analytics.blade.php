<x-layout>
    <div class="w-full max-w-6xl mx-auto pb-12">

        {{-- Header --}}
        <header class="mb-8 pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">
                        Platform Analytics
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
                        Crop pricing trends, logistics efficiency, and baseline price management
                    </p>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/20 px-3 py-1.5 rounded-lg border border-rose-500/10 dark:border-rose-500/20 self-start">
                    Admin Only
                </span>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-[#3A7D44]/10 border border-[#3A7D44]/20 text-[#1A2E1A] text-xs font-bold heading-font rounded-xl px-4 py-3 mb-6">
                ✅ {{ session('success') }}
            </div>
        @endif

        {{-- ═══════════════════════════════════════════ --}}
        {{-- SECTION 1: CROP PRICING TRENDS             --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Crop Pricing Trends</h2>
            <span class="w-32 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        @if($cropPricingTrends->isEmpty())
            <div class="bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-8 text-center shadow-sm mb-8">
                <p class="text-slate-400 text-sm font-semibold">No completed negotiations yet. Price trends will appear here once deals are closed.</p>
            </div>
        @else
            <div class="bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl overflow-hidden shadow-sm mb-8">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/30 border-b border-slate-100 dark:border-slate-700/60">
                            <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Crop</th>
                            <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">Avg ₱/kg</th>
                            <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">Min</th>
                            <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">Max</th>
                            <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">Deals</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                        @foreach($cropPricingTrends as $trend)
                            <tr class="hover:bg-slate-50/40 dark:hover:bg-slate-900/10 transition">
                                <td class="px-5 py-3 font-bold text-slate-700 dark:text-slate-300">🌾 {{ $trend->crop_name }}</td>
                                <td class="px-5 py-3 text-right font-mono text-[#3A7D44] dark:text-[#3A7D44] font-bold">₱{{ number_format($trend->avg_price, 2) }}</td>
                                <td class="px-5 py-3 text-right font-mono text-slate-400">₱{{ number_format($trend->min_price, 2) }}</td>
                                <td class="px-5 py-3 text-right font-mono text-slate-400">₱{{ number_format($trend->max_price, 2) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <span class="bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-[10px] font-bold px-2 py-0.5 rounded-md">
                                        {{ $trend->deal_count }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Weekly Price Chart (Simple Bar Chart via CSS) --}}
            @if($weeklyPrices->isNotEmpty())
                <div class="bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-6 shadow-sm mb-8">
                    <h3 class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-4">Weekly Average Pricing (Last 12 Weeks)</h3>
                    @foreach($weeklyPrices as $cropName => $weeks)
                        <div class="mb-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">{{ $cropName }}</p>
                            <div class="flex items-end gap-1 h-16">
                                @php
                                    $maxPrice = $weeks->max('avg_price') ?: 1;
                                @endphp
                                @foreach($weeks as $week)
                                    @php $heightPercent = ($week->avg_price / $maxPrice) * 100; @endphp
                                    <div class="flex-1 bg-gradient-to-t from-[#3A7D44] to-[#2E6336] rounded-t-sm opacity-80 hover:opacity-100 transition relative group cursor-default"
                                         style="height: {{ $heightPercent }}%; min-height: 4px;">
                                        <div class="absolute -top-6 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[8px] font-bold px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-10">
                                            ₱{{ number_format($week->avg_price, 2) }} · W{{ substr($week->week, -2) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif

        {{-- ═══════════════════════════════════════════ --}}
        {{-- SECTION 2: LOGISTICS EFFICIENCY             --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Logistics Efficiency</h2>
            <span class="w-32 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Trips</p>
                <p class="text-2xl font-bold text-slate-800 dark:text-slate-200">{{ $fleetMetrics->total_trips ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Avg Trip Duration</p>
                <p class="text-2xl font-bold text-[#1F4D25] dark:text-[#1F4D25]">
                    {{ $fleetMetrics->avg_trip_days ? number_format($fleetMetrics->avg_trip_days, 1) . 'd' : '—' }}
                </p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Fuel Spent</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">₱{{ number_format($totalFuelCost, 0) }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">{{ number_format($totalFuelLiters, 1) }}L across {{ $totalFuelLogs }} logs</p>
            </div>
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Avg KPL</p>
                <p class="text-2xl font-bold text-[#3A7D44] dark:text-[#3A7D44]">{{ $avgKpl > 0 ? $avgKpl : '—' }}</p>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- SECTION 3: BASELINE PRICE MANAGEMENT        --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Baseline Price Management</h2>
            <span class="w-32 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        <div class="bg-white dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl overflow-hidden shadow-sm mb-8">
            <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-700/60 bg-slate-50 dark:bg-slate-900/30">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    Edit baseline prices used for farmer pricing guidance. These can be manually overridden here when the scraper is unavailable.
                </p>
            </div>
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-700/60">
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Crop</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">Current Baseline</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">Update</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                    @foreach($crops as $crop)
                        <tr class="hover:bg-slate-50/40 dark:hover:bg-slate-900/10 transition">
                            <td class="px-5 py-3 font-bold text-slate-700 dark:text-slate-300">{{ $crop->name }}</td>
                            <td class="px-5 py-3 text-right font-mono">
                                @if($crop->baseline_price_per_kg)
                                    <span class="text-[#3A7D44] dark:text-[#3A7D44] font-bold">₱{{ number_format($crop->baseline_price_per_kg, 2) }}/kg</span>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600 italic">Not set</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('admin.baseline-price', $crop) }}" class="inline-flex items-center gap-2">
                                    @csrf
                                    <input type="number" name="baseline_price_per_kg" step="0.01" min="0.01"
                                           value="{{ $crop->baseline_price_per_kg }}"
                                           placeholder="₱/kg"
                                           class="w-24 text-right text-xs border border-slate-200 dark:border-slate-600 rounded-lg px-2 py-1.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-[#3A7D44]/30 focus:border-[#3A7D44]">
                                    <button type="submit"
                                            class="text-[10px] font-bold uppercase px-2.5 py-1.5 bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 text-[#3A7D44] dark:text-[#3A7D44] border border-[#3A7D44]/20 dark:border-[#3A7D44]/20 rounded-lg hover:bg-[#3A7D44]/15 hover:bg-[#3A7D44]/20 transition cursor-pointer">
                                        Save
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</x-layout>
