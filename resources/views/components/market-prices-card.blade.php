@props(['daPrices' => collect(), 'priceTrends' => collect(), 'latestDate' => null, 'scraperStatus' => null])

@php
    $dataIsStale = false;
    if ($latestDate) {
        $dataAgeHours = \Carbon\Carbon::parse($latestDate)->diffInHours(now(), false);
        $dataIsStale = abs($dataAgeHours) > 24;
    }
    $scraperFailed = $scraperStatus && in_array($scraperStatus['status'] ?? '', ['failed', 'never_run']);

    $grouped = $priceTrends->groupBy('category');
    $categoryOrder = ['Rice', 'Corn', 'Lowland Vegetables', 'Highland Vegetables', 'Root Crops', 'Fruits', 'Spices', 'Legumes', 'Coconut Products', 'Other Crops'];
    $sortedCategories = collect($categoryOrder)->filter(fn($cat) => $grouped->has($cat));
@endphp

<div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl overflow-hidden">

    {{-- Header --}}
    <div class="px-6 pt-6 pb-4 border-b border-slate-100 dark:border-slate-700/50">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white heading-font">DA RFO12 Market Prices</h2>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 font-medium">Average Daily Price Index of Agricultural Commodities in SOCCSKSARGEN Region</p>
            </div>
            <div class="flex items-center gap-2">
                @if($scraperFailed)
                    <span class="text-[9px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 border border-amber-200/50 dark:border-amber-700/30 px-2 py-0.5 rounded flex items-center gap-1">
                        <svg class="w-2.5 h-2.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        Scraper Error
                    </span>
                @endif
                @if($latestDate)
                    <span class="text-[9px] font-bold {{ $dataIsStale ? 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 border-amber-200/50 dark:border-amber-700/30' : 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 border-blue-200/50 dark:border-blue-700/30' }} px-2 py-0.5 rounded border">
                        {{ \Carbon\Carbon::parse($latestDate)->format('M d, Y') }}
                        @if($dataIsStale)
                            — {{ \Carbon\Carbon::parse($latestDate)->diffForHumans(null, true) }} old
                        @endif
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if($scraperFailed && $scraperStatus)
        <div class="mx-6 mt-4 px-3 py-2 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200/60 dark:border-amber-700/30">
            <p class="text-[10px] font-bold text-amber-700 dark:text-amber-400">
                @if($scraperStatus['status'] === 'never_run')
                    Price scraper has not run yet. Showing empty data.
                @else
                    Last scraper run failed on {{ $scraperStatus['last_run_at'] ? \Carbon\Carbon::parse($scraperStatus['last_run_at'])->format('M d, Y h:i A') : 'unknown date' }}. Showing oldest available data.
                @endif
            </p>
        </div>
    @endif

    @if($daPrices->isEmpty())
        <div class="py-12 text-center">
            <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p class="text-xs text-slate-400 dark:text-slate-500 font-semibold">No price data available yet.</p>
            <p class="text-[10px] text-slate-300 dark:text-slate-600 mt-1">Prices are scraped periodically from DA RFO12 throughout the day.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/30">
                        <th class="px-6 py-2.5 text-[9px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500">Category</th>
                        <th class="px-3 py-2.5 text-[9px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500">Commodity</th>
                        <th class="px-3 py-2.5 text-[9px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500 text-right">Low (₱/kg)</th>
                        <th class="px-3 py-2.5 text-[9px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500 text-right">High (₱/kg)</th>
                        <th class="px-3 py-2.5 text-[9px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500 text-right">Common (Retail)</th>
                        <th class="px-3 py-2.5 text-[9px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500 text-right">DPI (Prevailing Avg)</th>
                        <th class="px-6 py-2.5 text-[9px] font-extrabold uppercase tracking-widest text-slate-400 dark:text-slate-500 text-right w-16">Trend</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/30">
                    @foreach($sortedCategories as $category)
                        {{-- Category Header Row --}}
                        <tr class="bg-slate-50/80 dark:bg-slate-900/20">
                            <td colspan="7" class="px-6 py-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-[#3A7D44] dark:text-[#4ea857]">{{ $category }}</span>
                                    <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500">({{ $grouped[$category]->count() }} {{ Str::plural('item', $grouped[$category]->count()) }})</span>
                                    <span class="flex-1 h-px bg-slate-200/60 dark:bg-slate-700/40"></span>
                                </div>
                            </td>
                        </tr>

                        {{-- Commodity Rows --}}
                        @foreach($grouped[$category] as $trend)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                <td class="px-6 py-2 text-[10px] font-bold text-slate-400 dark:text-slate-500">{{ $category }}</td>
                                <td class="px-3 py-2 text-[11px] font-bold text-slate-800 dark:text-slate-200">{{ $trend['commodity'] }}</td>
                                <td class="px-3 py-2 text-[11px] font-mono font-extrabold text-slate-600 dark:text-slate-400 text-right">
                                    @if($trend['low'])
                                        ₱{{ number_format($trend['low'], 2) }}
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-[11px] font-mono font-extrabold text-slate-600 dark:text-slate-400 text-right">
                                    @if($trend['high'])
                                        ₱{{ number_format($trend['high'], 2) }}
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-[11px] font-mono font-extrabold text-slate-700 dark:text-slate-300 text-right">
                                    @if($trend['common'])
                                        ₱{{ number_format($trend['common'], 2) }}
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-[11px] font-mono font-extrabold text-slate-900 dark:text-white text-right">
                                    ₱{{ number_format($trend['price'], 2) }}
                                </td>
                                <td class="px-6 py-2 text-right">
                                    @if($trend['trend'] === 'up')
                                        <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-1.5 py-0.5 rounded">▲ +{{ $trend['change_pct'] }}%</span>
                                    @elseif($trend['trend'] === 'down')
                                        <span class="text-[9px] font-bold text-red-500 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-1.5 py-0.5 rounded">▼ {{ $trend['change_pct'] }}%</span>
                                    @else
                                        <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-800 px-1.5 py-0.5 rounded">▬ Stable</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Footer --}}
    <div class="px-6 py-3 border-t border-slate-100 dark:border-slate-700/50 bg-slate-50/30 dark:bg-slate-900/20 flex items-center justify-between">
        <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium">Source: DA RFO12 SOCCSKSARGEN</p>
        @if($daPrices->isNotEmpty())
            <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium">{{ $daPrices->count() }} commodities listed</p>
        @endif
    </div>
</div>
