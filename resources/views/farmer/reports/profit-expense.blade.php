<x-layout title="Profit & Expense Report — HarvestHaul">
    <div class="w-full max-w-7xl mx-auto pb-12 px-4 sm:px-6 lg:px-8">

        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-[#3A7D44]/10 border border-[#3A7D44]/20 flex items-center justify-center text-[#3A7D44]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-800 dark:text-white heading-font">Profit & Expense Report</h1>
                    <p class="text-xs text-slate-400 font-semibold mt-0.5">Financial overview of your crop sales and logistics costs</p>
                </div>
            </div>
        </div>

        <!-- Date Range Filter -->
        <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">From</label>
                <input type="date" name="from" value="{{ $dateFrom }}"
                    class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-white dark:bg-slate-800">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">To</label>
                <input type="date" name="to" value="{{ $dateTo }}"
                    class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 font-bold focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] bg-white dark:bg-slate-800">
            </div>
            <button type="submit" class="px-5 py-2 bg-[#3A7D44] hover:bg-[#2E6336] text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                Filter
            </button>
        </form>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Revenue</p>
                <p class="text-2xl font-black text-[#3A7D44] heading-font mt-2">₱{{ number_format($totalRevenue, 2) }}</p>
                <p class="text-[10px] text-slate-400 mt-1">{{ $negotiations->count() }} completed deal{{ $negotiations->count() !== 1 ? 's' : '' }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Costs</p>
                <p class="text-2xl font-black text-rose-500 heading-font mt-2">₱{{ number_format($totalCosts, 2) }}</p>
                <p class="text-[10px] text-slate-400 mt-1">Logistics cost shares</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Net Profit</p>
                <p class="text-2xl font-black {{ $netProfit >= 0 ? 'text-[#3A7D44]' : 'text-rose-500' }} heading-font mt-2">₱{{ number_format($netProfit, 2) }}</p>
                <p class="text-[10px] text-slate-400 mt-1">{{ $netProfit >= 0 ? 'Positive' : 'Negative' }} margin</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue by Crop -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Revenue by Crop</h2>
                </div>
                <div class="p-5">
                    @forelse($revenueByCrop as $crop => $data)
                        <div class="flex items-center justify-between py-2.5 @if(!$loop->last) border-b border-slate-50 dark:border-slate-700/30 @endif">
                            <div>
                                <p class="text-xs font-bold text-slate-800 dark:text-white">{{ $crop }}</p>
                                <p class="text-[10px] text-slate-400">{{ $data['count'] }} sale{{ $data['count'] !== 1 ? 's' : '' }}</p>
                            </div>
                            <p class="text-sm font-black text-[#3A7D44] heading-font">₱{{ number_format($data['total'], 2) }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-8">No completed sales in this period.</p>
                    @endforelse
                </div>
            </div>

            <!-- Cost Breakdown -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Cost Breakdown</h2>
                </div>
                <div class="p-5">
                    @forelse($costBreakdown as $crop => $cost)
                        <div class="flex items-center justify-between py-2.5 @if(!$loop->last) border-b border-slate-50 dark:border-slate-700/30 @endif">
                            <p class="text-xs font-bold text-slate-800 dark:text-white">{{ $crop }}</p>
                            <p class="text-sm font-black text-rose-500 heading-font">₱{{ number_format($cost, 2) }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-8">No logistics costs in this period.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Monthly Trend -->
        @if(count($monthlyTrend) > 0)
            <div class="mt-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Monthly Trend</h2>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        @foreach($monthlyTrend as $month => $data)
                            <div class="text-center p-3 rounded-xl bg-slate-50 dark:bg-slate-700/30 border border-slate-100 dark:border-slate-700/30">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">{{ Carbon\Carbon::parse($month . '-01')->format('M Y') }}</p>
                                <p class="text-lg font-black text-[#3A7D44] heading-font mt-1">₱{{ number_format($data['revenue'], 0) }}</p>
                                <p class="text-[10px] text-slate-400">{{ $data['count'] }} deal{{ $data['count'] !== 1 ? 's' : '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Active Harvests -->
        @if($activeHarvests->count() > 0)
            <div class="mt-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Active Harvests (Unsold/Partial)</h2>
                </div>
                <div class="p-5">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50">
                                    <th class="text-left py-2 px-3">Crop</th>
                                    <th class="text-right py-2 px-3">Quantity</th>
                                    <th class="text-right py-2 px-3">Suggested Price</th>
                                    <th class="text-center py-2 px-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeHarvests as $harvest)
                                    <tr class="border-b border-slate-50 dark:border-slate-700/30">
                                        <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-white">{{ $harvest->crop->name ?? 'Unknown' }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold">{{ number_format($harvest->quantity_kg, 1) }} kg</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-[#3A7D44]">₱{{ number_format($harvest->suggested_price_per_kg ?? 0, 2) }}/kg</td>
                                        <td class="py-2.5 px-3 text-center">
                                            <span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded-full
                                                @if($harvest->status === 'active') bg-[#3A7D44]/10 text-[#3A7D44]
                                                @elseif($harvest->status === 'negotiating') bg-amber-500/10 text-amber-600
                                                @elseif($harvest->status === 'partially_sold') bg-blue-500/10 text-blue-600
                                                @endif">
                                                {{ ucfirst(str_replace('_', ' ', $harvest->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-layout>
