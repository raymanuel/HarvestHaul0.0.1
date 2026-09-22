<x-layout title="Purchase Reports — HarvestHaul">
    <div class="w-full max-w-7xl mx-auto pb-12 px-4 sm:px-6 lg:px-8">

        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Purchase Reports</h1>
            <a href="{{ route('buyer.reports.download', request()->query()) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#16283C] hover:bg-[#0E1620] text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-xs font-bold rounded-xl transition-all shadow-sm">
                Export CSV
            </a>
        </div>

        <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
            <div>
                <label for="from" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">From</label>
                <input type="date" name="from" id="from" value="{{ request('from', $dateFrom) }}"
                    class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold bg-white dark:bg-slate-800">
            </div>
            <div>
                <label for="to" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">To</label>
                <input type="date" name="to" id="to" value="{{ request('to', $dateTo) }}"
                    class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold bg-white dark:bg-slate-800">
            </div>
            <button type="submit" class="px-5 py-2 bg-[#16283C] hover:bg-[#0E1620] text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-xs font-bold rounded-xl transition-all shadow-sm">
                Apply
            </button>
        </form>

        @php
            $rangeLabel = \Carbon\Carbon::parse($dateFrom)->format('M d, Y') . ' – ' . \Carbon\Carbon::parse($dateTo)->format('M d, Y');
        @endphp

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Spent</p>
                <p class="text-2xl font-black text-[#16283C] dark:text-[#D7BC7A] heading-font mt-2">&#8369;{{ number_format($totalSpent, 2) }}</p>
                <p class="text-[10px] text-slate-400 mt-1">{{ $rangeLabel }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Volume Purchased</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white heading-font mt-2">{{ number_format($totalKg, 1) }}<span class="text-sm font-semibold text-slate-400 ml-1">kg</span></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Avg Price Paid</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white heading-font mt-2">&#8369;{{ number_format($avgPricePerKg, 2) }}<span class="text-sm font-semibold text-slate-400 ml-1">/kg</span></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Completed Purchases</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white heading-font mt-2">{{ $totalPurchases }}</p>
            </div>
        </div>

        @if($totalPurchases === 0)
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 shadow-sm p-12 text-center mb-8">
                <p class="text-slate-400 text-sm font-semibold">No completed purchases in this period.</p>
                <p class="text-slate-500 dark:text-slate-400 text-xs mt-1">Try a wider date range.</p>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50 flex items-center justify-between flex-wrap gap-2">
                        <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Spend by Crop</h2>
                        <span class="text-[10px] font-bold text-slate-400">What you bought most</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50">
                                    <th class="text-left py-3 px-5">Crop</th>
                                    <th class="text-right py-3 px-3">Deals</th>
                                    <th class="text-right py-3 px-3">Kg</th>
                                    <th class="text-right py-3 px-3">Avg /kg</th>
                                    <th class="text-right py-3 px-5">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($spendByCrop as $cropName => $row)
                                    <tr class="border-b border-slate-50 dark:border-slate-700/30">
                                        <td class="py-3 px-5 font-bold text-slate-800 dark:text-white">{{ $cropName }}</td>
                                        <td class="py-3 px-3 text-right font-bold">{{ $row['deals'] }}</td>
                                        <td class="py-3 px-3 text-right font-bold">{{ number_format($row['kg'], 1) }}</td>
                                        <td class="py-3 px-3 text-right font-bold">&#8369;{{ number_format($row['avg_price'], 2) }}</td>
                                        <td class="py-3 px-5 text-right font-extrabold text-[#16283C] dark:text-[#D7BC7A]">&#8369;{{ number_format($row['total'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                        <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Month by Month</h2>
                    </div>
                    <div class="p-5 grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($monthlyTrend as $monthKey => $row)
                            <div class="text-center p-3 rounded-xl bg-slate-50 dark:bg-slate-700/30 border border-slate-100 dark:border-slate-700/30">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">{{ \Carbon\Carbon::parse($monthKey . '-01')->format('M Y') }}</p>
                                <p class="text-lg font-black text-[#16283C] dark:text-[#D7BC7A] heading-font mt-1">&#8369;{{ number_format($row['spent'], 0) }}</p>
                                <p class="text-[10px] text-slate-400">{{ number_format($row['kg'], 0) }} kg &middot; {{ $row['deals'] }} deal{{ $row['deals'] !== 1 ? 's' : '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50 flex items-center justify-between">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Purchase Log</h2>
                    <span class="text-[10px] font-bold text-slate-400">{{ $rangeLabel }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50">
                                <th class="text-left py-3 px-5">Date</th>
                                <th class="text-left py-3 px-3">Crop</th>
                                <th class="text-left py-3 px-3">Farmer</th>
                                <th class="text-right py-3 px-3">Price</th>
                                <th class="text-right py-3 px-3">Volume</th>
                                <th class="text-right py-3 px-5">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchases as $purchase)
                                <tr class="border-b border-slate-50 dark:border-slate-700/30">
                                    <td class="py-3 px-5 text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ \Carbon\Carbon::parse($purchase->updated_at)->format('M d, Y') }}</td>
                                    <td class="py-3 px-3 font-bold text-slate-800 dark:text-white">{{ $purchase->harvest->crop->name ?? 'Unknown' }}</td>
                                    <td class="py-3 px-3 text-slate-600 dark:text-slate-300">{{ $purchase->harvest->farmer->name ?? '&mdash;' }}</td>
                                    <td class="py-3 px-3 text-right font-bold">&#8369;{{ number_format((float) ($purchase->negotiated_price ?? 0), 2) }}/kg</td>
                                    <td class="py-3 px-3 text-right font-bold">{{ number_format((float) ($purchase->negotiated_volume ?? 0), 1) }} kg</td>
                                    <td class="py-3 px-5 text-right font-extrabold text-[#16283C] dark:text-[#D7BC7A]">&#8369;{{ number_format((float) ($purchase->negotiated_price ?? 0) * (float) ($purchase->negotiated_volume ?? 0), 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</x-layout>