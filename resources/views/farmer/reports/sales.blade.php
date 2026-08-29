<x-layout title="Sales Report — HarvestHaul">
    <div class="w-full max-w-7xl mx-auto pb-12 px-4 sm:px-6 lg:px-8">

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Financial Reports</h1>
        </div>

        <!-- Report Tabs -->
        @php
            $reportTabs = [
                ['label' => 'Sales', 'url' => route('farmer.reports.sales'), 'active' => true],
                ['label' => 'Profit & Expense', 'url' => route('farmer.reports.profit-expense'), 'active' => false],
            ];
        @endphp
        <x-nav-tabs :tabs="$reportTabs" />

        <!-- Filters -->
        <form method="GET" class="mb-6 flex flex-wrap items-end gap-3" id="sales-filter-form">
            <div>
                <label for="period" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Period</label>
                <select name="period" id="period" onchange="toggleCustomDates(); this.form.submit()"
                        class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C] bg-white dark:bg-slate-800">
                    <option value="this_month" {{ $preset === 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ $preset === 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_quarter" {{ $preset === 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                    <option value="last_quarter" {{ $preset === 'last_quarter' ? 'selected' : '' }}>Last Quarter</option>
                    <option value="year" {{ $preset === 'year' ? 'selected' : '' }}>Whole Year</option>
                    <option value="custom" {{ $preset === 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>
            <div id="year-wrap" class="{{ $preset === 'year' ? '' : 'hidden' }}">
                <label for="y" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Year</label>
                <select name="y" id="y"
                        class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold bg-white dark:bg-slate-800">
                    @for($yy = now()->year; $yy >= now()->year - 5; $yy--)
                        <option value="{{ $yy }}" {{ $year === $yy ? 'selected' : '' }}>{{ $yy }}</option>
                    @endfor
                </select>
            </div>
            <div id="custom-wrap" class="flex flex-wrap items-end gap-3 {{ $preset === 'custom' ? '' : 'hidden' }}">
                <div>
                    <label for="from" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">From</label>
                    <input type="date" name="from" id="from" value="{{ request('from', $dateFrom->toDateString()) }}"
                        class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold bg-white dark:bg-slate-800">
                </div>
                <div>
                    <label for="to" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">To</label>
                    <input type="date" name="to" id="to" value="{{ request('to', $dateTo->toDateString()) }}"
                        class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold bg-white dark:bg-slate-800">
                </div>
            </div>
            <div>
                <label for="crop_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Crop</label>
                <select name="crop_id" id="crop_id" onchange="this.form.submit()"
                        class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold bg-white dark:bg-slate-800">
                    <option value="">All crops</option>
                    @foreach($soldCrops as $sc)
                        <option value="{{ $sc->id }}" {{ (string) $cropId === (string) $sc->id ? 'selected' : '' }}>{{ $sc->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-[#16283C] hover:bg-[#0E1620] text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                Apply
            </button>
        </form>

        {{-- Keep period context when the year/custom inputs change --}}
        <script>
            function toggleCustomDates() {
                const p = document.getElementById('period').value;
                document.getElementById('custom-wrap').classList.toggle('hidden', p !== 'custom');
                document.getElementById('year-wrap').classList.toggle('hidden', p !== 'year');
            }
        </script>

        @php
            $rangeLabel = $dateFrom->format('M d, Y') . ' – ' . $dateTo->format('M d, Y');
        @endphp

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Gross Earnings</p>
                <p class="text-2xl font-black text-[#16283C] heading-font mt-2">&#8369;{{ number_format($gross, 2) }}</p>
                <p class="text-[10px] text-slate-400 mt-1">{{ $rangeLabel }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Volume Sold</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white heading-font mt-2">{{ number_format($kgSold, 1) }}<span class="text-sm font-semibold text-slate-400 ml-1">kg</span></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Avg Achieved Price</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white heading-font mt-2">&#8369;{{ number_format($avgPrice, 2) }}<span class="text-sm font-semibold text-slate-400 ml-1">/kg</span></p>
                <p class="text-[10px] text-slate-400 mt-1">Weighted by volume sold</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Completed Deals</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white heading-font mt-2">{{ $dealCount }}</p>
            </div>
        </div>

        @if($dealCount === 0)
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 shadow-sm p-12 text-center mb-8">
                <p class="text-slate-400 text-sm font-semibold">No completed sales in this period.</p>
                <p class="text-slate-500 dark:text-slate-400 text-xs mt-1">Try a wider range or clear the crop filter.</p>
            </div>
        @else

            <!-- Price-Point Comparison -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm mb-6">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50 flex items-center justify-between flex-wrap gap-2">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Price-Point Comparison</h2>
                    <span class="text-[10px] font-bold text-slate-400">Same crop at different prices, side by side</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50">
                                <th class="text-left py-3 px-5">Crop</th>
                                <th class="text-right py-3 px-3">Sold At</th>
                                <th class="text-right py-3 px-3">Deals</th>
                                <th class="text-right py-3 px-3">Kg Sold</th>
                                <th class="text-right py-3 px-3">Gross Earnings</th>
                                <th class="text-right py-3 px-5">% of Volume</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pricePoints as $pp)
                                <tr class="border-b border-slate-50 dark:border-slate-700/30">
                                    <td class="py-3 px-5 font-bold text-slate-800 dark:text-white">{{ $pp['crop'] }}</td>
                                    <td class="py-3 px-3 text-right font-extrabold text-[#16283C]">&#8369;{{ number_format($pp['price'], 2) }}/kg</td>
                                    <td class="py-3 px-3 text-right font-bold">{{ $pp['deals'] }}</td>
                                    <td class="py-3 px-3 text-right font-bold">{{ number_format($pp['kg'], 1) }}</td>
                                    <td class="py-3 px-3 text-right font-extrabold text-[#16283C]">&#8369;{{ number_format($pp['earnings'], 2) }}</td>
                                    <td class="py-3 px-5 text-right">
                                        <div class="inline-flex items-center gap-2 justify-end w-full max-w-[140px]">
                                            <div class="h-1.5 rounded-full bg-slate-100 dark:bg-slate-700 flex-1 overflow-hidden">
                                                <div class="h-full rounded-full bg-[#16283C]" style="width: {{ min(100, $pp['pct']) }}%"></div>
                                            </div>
                                            <span class="font-bold">{{ $pp['pct'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- vs DA Market Reference + Monthly Trend -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

                <!-- Market benchmark -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                        <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Your Price vs DA Market Reference</h2>
                        <p class="text-[10px] text-slate-400 mt-0.5">Average prevailing price (DPI) from the DA RFO12 market bulletin over the same period. Reference only.</p>
                    </div>
                    @php
                        $anyBenchmark = collect($benchmark)->isNotEmpty();
                    @endphp
                    @if(!$anyBenchmark)
                        <div class="p-8 text-center">
                            <p class="text-slate-400 text-xs font-semibold">No DA market data collected for these crops in this period.</p>
                            <p class="text-slate-500 dark:text-slate-400 text-[10px] mt-1">Market prices are gathered hourly from the DA RFO12 bulletin; history builds up over time.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50">
                                        <th class="text-left py-3 px-5">Crop</th>
                                        <th class="text-right py-3 px-3">Your Avg</th>
                                        <th class="text-right py-3 px-3">DA Ref Avg</th>
                                        <th class="text-right py-3 px-5">Difference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($achievedByCrop as $cid => $row)
                                        @continue(!isset($benchmark[$cid]))
                                        @php
                                            $diff = round($row['avg'] - $benchmark[$cid], 2);
                                            $diffPct = $benchmark[$cid] > 0 ? round(($diff / $benchmark[$cid]) * 100, 1) : null;
                                        @endphp
                                        <tr class="border-b border-slate-50 dark:border-slate-700/30">
                                            <td class="py-3 px-5 font-bold text-slate-800 dark:text-white">{{ $row['crop'] }}</td>
                                            <td class="py-3 px-3 text-right font-bold">&#8369;{{ number_format($row['avg'], 2) }}</td>
                                            <td class="py-3 px-3 text-right font-bold text-slate-500">&#8369;{{ number_format($benchmark[$cid], 2) }}</td>
                                            <td class="py-3 px-5 text-right font-extrabold {{ $diff >= 0 ? 'text-[#16283C]' : 'text-rose-500' }}">
                                                {{ $diff >= 0 ? '+' : '' }}&#8369;{{ number_format($diff, 2) }}
                                                @if($diffPct !== null)
                                                    <span class="text-[10px] font-semibold text-slate-400">({{ $diff >= 0 ? '+' : '' }}{{ $diffPct }}%)</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Monthly trend -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                        <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Month by Month</h2>
                    </div>
                    <div class="p-5 grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($monthlyTrend as $monthKey => $m)
                            <div class="text-center p-3 rounded-xl bg-slate-50 dark:bg-slate-700/30 border border-slate-100 dark:border-slate-700/30">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">{{ \Carbon\Carbon::parse($monthKey . '-01')->format('M Y') }}</p>
                                <p class="text-lg font-black text-[#16283C] heading-font mt-1">&#8369;{{ number_format($m['gross'], 0) }}</p>
                                <p class="text-[10px] text-slate-400">{{ number_format($m['kg'], 0) }} kg &middot; {{ $m['deals'] }} deal{{ $m['deals'] !== 1 ? 's' : '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Deal Log -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50 flex items-center justify-between">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Deal Log</h2>
                    <span class="text-[10px] font-bold text-slate-400">{{ $rangeLabel }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50">
                                <th class="text-left py-3 px-5">Deal Date</th>
                                <th class="text-left py-3 px-3">Crop</th>
                                <th class="text-left py-3 px-3">Buyer</th>
                                <th class="text-right py-3 px-3">Price</th>
                                <th class="text-right py-3 px-3">Volume</th>
                                <th class="text-right py-3 px-5">Gross</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deals as $deal)
                                <tr class="border-b border-slate-50 dark:border-slate-700/30">
                                    <td class="py-3 px-5 text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ \Carbon\Carbon::parse($deal->updated_at)->format('M d, Y') }}</td>
                                    <td class="py-3 px-3 font-bold text-slate-800 dark:text-white">{{ $deal->harvest->crop->name ?? 'Unknown' }}</td>
                                    <td class="py-3 px-3 text-slate-600 dark:text-slate-300">{{ $deal->buyer->name ?? '&mdash;' }}</td>
                                    <td class="py-3 px-3 text-right font-bold">&#8369;{{ number_format((float) ($deal->negotiated_price ?? 0), 2) }}/kg</td>
                                    <td class="py-3 px-3 text-right font-bold">{{ number_format((float) ($deal->negotiated_volume ?? 0), 1) }} kg</td>
                                    <td class="py-3 px-5 text-right font-extrabold text-[#16283C]">&#8369;{{ number_format((float) ($deal->negotiated_price ?? 0) * (float) ($deal->negotiated_volume ?? 0), 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</x-layout>
