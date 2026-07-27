<x-layout title="Trip Report — HarvestHaul">
    <div class="w-full max-w-7xl mx-auto pb-12 px-4 sm:px-6 lg:px-8">

        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-[#3A7D44]/10 border border-[#3A7D44]/20 flex items-center justify-center text-[#3A7D44]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-800 dark:text-white heading-font">Trip Report</h1>
                    <p class="text-xs text-slate-400 font-semibold mt-0.5">Fleet performance, revenue, and fuel analysis</p>
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
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Completed Trips</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white heading-font mt-2">{{ $totalTrips }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Revenue</p>
                <p class="text-2xl font-black text-[#3A7D44] heading-font mt-2">₱{{ number_format($totalRevenue, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Fuel Cost</p>
                <p class="text-2xl font-black text-amber-500 heading-font mt-2">₱{{ number_format($totalFuelCost, 2) }}</p>
                <p class="text-[10px] text-slate-400 mt-1">{{ number_format($totalFuelLiters, 1) }} liters</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 p-5 shadow-sm">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Net Income</p>
                <p class="text-2xl font-black {{ $netIncome >= 0 ? 'text-[#3A7D44]' : 'text-rose-500' }} heading-font mt-2">₱{{ number_format($netIncome, 2) }}</p>
                <p class="text-[10px] text-slate-400 mt-1">{{ number_format($totalKg, 1) }} kg delivered</p>
            </div>
        </div>

        <!-- Per-Truck Metrics -->
        @if(count($truckMetrics) > 0)
            <div class="mb-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Per-Truck Performance</h2>
                </div>
                <div class="p-5">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50">
                                    <th class="text-left py-2 px-3">Truck</th>
                                    <th class="text-right py-2 px-3">Trips</th>
                                    <th class="text-right py-2 px-3">Revenue</th>
                                    <th class="text-right py-2 px-3">Fuel Cost</th>
                                    <th class="text-right py-2 px-3">Net Income</th>
                                    <th class="text-right py-2 px-3">KPL</th>
                                    <th class="text-right py-2 px-3">Avg Load</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($truckMetrics as $m)
                                    <tr class="border-b border-slate-50 dark:border-slate-700/30">
                                        <td class="py-2.5 px-3">
                                            <p class="font-bold text-slate-800 dark:text-white">{{ $m['truck']->plate_number ?? '—' }}</p>
                                            <p class="text-[10px] text-slate-400">{{ $m['truck']->vehicle_type ?? '' }}</p>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-bold">{{ $m['trips'] }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-[#3A7D44]">₱{{ number_format($m['revenue'], 0) }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-amber-500">₱{{ number_format($m['fuel_cost'], 0) }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold {{ $m['net_income'] >= 0 ? 'text-[#3A7D44]' : 'text-rose-500' }}">
                                            ₱{{ number_format($m['net_income'], 0) }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-bold">{{ $m['kpl'] }} km/L</td>
                                        <td class="py-2.5 px-3 text-right font-bold">{{ number_format($m['avg_load'], 1) }} kg</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Monthly Trend -->
        @if(count($monthlyTrend) > 0)
            <div class="mb-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Monthly Trend</h2>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        @foreach($monthlyTrend as $month => $data)
                            <div class="text-center p-3 rounded-xl bg-slate-50 dark:bg-slate-700/30 border border-slate-100 dark:border-slate-700/30">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">{{ Carbon\Carbon::parse($month . '-01')->format('M Y') }}</p>
                                <p class="text-lg font-black text-[#3A7D44] heading-font mt-1">₱{{ number_format($data['revenue'], 0) }}</p>
                                <p class="text-[10px] text-slate-400">{{ $data['trips'] }} trip{{ $data['trips'] !== 1 ? 's' : '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Trip Log -->
        @if($trips->count() > 0)
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/50">
                    <h2 class="text-sm font-black text-slate-800 dark:text-white heading-font">Trip Log</h2>
                </div>
                <div class="p-5">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700/50">
                                    <th class="text-left py-2 px-3">Job</th>
                                    <th class="text-left py-2 px-3">Truck</th>
                                    <th class="text-left py-2 px-3">Driver</th>
                                    <th class="text-right py-2 px-3">Kg</th>
                                    <th class="text-right py-2 px-3">Farms</th>
                                    <th class="text-right py-2 px-3">Revenue</th>
                                    <th class="text-center py-2 px-3">Completed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trips->take(50) as $trip)
                                    <tr class="border-b border-slate-50 dark:border-slate-700/30">
                                        <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-white">#{{ $trip->id }}</td>
                                        <td class="py-2.5 px-3 font-bold">{{ $trip->truck->plate_number ?? '—' }}</td>
                                        <td class="py-2.5 px-3">{{ $trip->driver->name ?? '—' }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold">{{ number_format($trip->total_kg, 1) }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold">{{ $trip->farm_count }}</td>
                                        <td class="py-2.5 px-3 text-right font-bold text-[#3A7D44]">₱{{ number_format($trip->negotiated_price ?? 0, 2) }}</td>
                                        <td class="py-2.5 px-3 text-center text-slate-400">{{ $trip->completed_at ? Carbon\Carbon::parse($trip->completed_at)->format('M d, Y') : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($trips->count() > 50)
                        <p class="text-[10px] text-slate-400 text-center mt-3">Showing 50 of {{ $trips->count() }} trips</p>
                    @endif
                </div>
            </div>
        @endif

    </div>
</x-layout>
