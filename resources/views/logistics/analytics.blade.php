<x-layout>
    <div class="w-full max-w-7xl mx-auto pb-12">

        <!-- Ambient glow decoration -->
        <div class="absolute top-0 right-1/4 w-96 h-96 rounded-full bg-emerald-500/5 blur-[120px] pointer-events-none z-0"></div>
        <div class="absolute top-1/3 left-1/3 w-[500px] h-[500px] rounded-full bg-sky-500/5 blur-[150px] pointer-events-none z-0"></div>

        <div class="relative z-10">
            <!-- Page Header -->
            <header class="mb-8 pt-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 dark:bg-emerald-400/10 px-3 py-1 rounded-full border border-emerald-500/20">Logistics Portal</span>
                        <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">
                            Fleet Analytics Hub
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
                            Real-time tracking of fuel efficiency (KPL), logistics expenditure, and revenue generation per vehicle.
                        </p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1.5 rounded-lg border border-emerald-500/10 dark:border-emerald-500/20 self-start">
                        Marketplace Statistics
                    </span>
                </div>
            </header>

            <!-- Overall Financial & Fuel Metrics Summary -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Trips Completed</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-white">{{ $truckAnalytics->sum('completed_trips') }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Cumulative Revenue</p>
                    <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">₱{{ number_format($totalRevenue, 2) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Fuel Expense</p>
                    <p class="text-2xl font-black text-rose-500">₱{{ number_format($totalFuelCost, 2) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Net Fleet Earnings</p>
                    @php
                        $netFleet = $totalRevenue - $totalFuelCost;
                    @endphp
                    <p class="text-2xl font-black {{ $netFleet >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">
                        ₱{{ number_format($netFleet, 2) }}
                    </p>
                </div>
            </div>

            <!-- Detailed Vehicle Statistics Table -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 heading-font">Revenue & Efficiency per Vehicle</h2>
                    <span class="text-[10px] font-bold text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2.5 py-1 rounded-lg border border-slate-200/60 dark:border-slate-700">
                        Operational Breakdown
                    </span>
                </div>

                @if($truckAnalytics->isEmpty())
                    <div class="p-12 text-center">
                        <p class="text-slate-400 text-sm font-semibold">No trucks currently registered.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-55 dark:bg-slate-900/30 border-b border-slate-100 dark:border-slate-700/60">
                                    <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Truck details</th>
                                    <th class="px-5 py-3 text-center text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Refuels</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Liters</th>
                                    <th class="px-5 py-3 text-center text-[10px] font-bold text-slate-400 uppercase tracking-wider">Fuel Efficiency (KPL)</th>
                                    <th class="px-5 py-3 text-center text-[10px] font-bold text-slate-400 uppercase tracking-wider">Trips</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Revenue</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Fuel Costs</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Net Return</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                @foreach($truckAnalytics as $analytics)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition">
                                        <td class="px-5 py-4">
                                            <p class="font-bold text-slate-800 dark:text-slate-200">{{ $analytics['truck_name'] }}</p>
                                            <p class="font-mono text-[10px] text-slate-400 mt-0.5">{{ $analytics['plate_number'] }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-center font-semibold text-slate-700 dark:text-slate-350">
                                            {{ $analytics['total_refuels'] }}
                                        </td>
                                        <td class="px-5 py-4 text-right font-semibold text-slate-700 dark:text-slate-350">
                                            {{ number_format($analytics['total_fuel_liters'], 1) }} L
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            @if($analytics['kpl'] > 0)
                                                @php
                                                    $efficiencyBadge = match(true) {
                                                        $analytics['kpl'] >= 6 => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/30',
                                                        $analytics['kpl'] >= 4 => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/50 dark:border-amber-800/30',
                                                        default => 'bg-rose-50 text-rose-705 dark:bg-rose-950/30 dark:text-rose-450 border border-rose-200/50 dark:border-rose-800/30',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold font-mono {{ $efficiencyBadge }}">
                                                    {{ $analytics['kpl'] }} km/L
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400 italic">Not enough logs</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-center font-semibold text-slate-700 dark:text-slate-350">
                                            {{ $analytics['completed_trips'] }}
                                        </td>
                                        <td class="px-5 py-4 text-right font-extrabold text-slate-800 dark:text-slate-200">
                                            ₱{{ number_format($analytics['revenue'], 2) }}
                                        </td>
                                        <td class="px-5 py-4 text-right font-semibold text-rose-500">
                                            ₱{{ number_format($analytics['total_fuel_cost'], 2) }}
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <span class="text-sm font-extrabold {{ $analytics['net_income'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">
                                                ₱{{ number_format($analytics['net_income'], 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Recent Fuel Purchases Ledger -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 heading-font">Recent Fuel Purchase Logs</h2>
                    <span class="text-[10px] font-bold text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2.5 py-1 rounded-lg border border-slate-200/60 dark:border-slate-700">
                        Driver Refuel Records
                    </span>
                </div>

                @if($fuelLogs->isEmpty())
                    <div class="p-12 text-center">
                        <p class="text-slate-400 text-sm font-semibold">No refuels logged yet.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-55 dark:bg-slate-900/30 border-b border-slate-100 dark:border-slate-700/60">
                                    <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Date</th>
                                    <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Vehicle</th>
                                    <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Driver</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Fuel Liters</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Cost</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Odometer</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                @foreach($fuelLogs as $log)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition">
                                        <td class="px-5 py-4 whitespace-nowrap text-slate-500 dark:text-slate-400 text-xs">
                                            {{ $log->created_at->format('M d, Y h:i A') }}
                                        </td>
                                        <td class="px-5 py-4">
                                            <p class="font-bold text-slate-800 dark:text-slate-200">{{ $log->truck->truck_name ?? '—' }}</p>
                                            <p class="font-mono text-[10px] text-slate-400 mt-0.5">{{ $log->truck->plate_number ?? '—' }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-slate-700 dark:text-slate-300 font-semibold">
                                            {{ $log->driver->name ?? '—' }}
                                        </td>
                                        <td class="px-5 py-4 text-right font-semibold text-slate-700 dark:text-slate-350">
                                            {{ number_format($log->fuel_liters, 2) }} L
                                        </td>
                                        <td class="px-5 py-4 text-right font-extrabold text-emerald-600 dark:text-emerald-400">
                                            ₱{{ number_format($log->cost, 2) }}
                                        </td>
                                        <td class="px-5 py-4 text-right font-mono text-xs text-slate-500 dark:text-slate-400">
                                            {{ number_format($log->odometer_reading, 1) }} km
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-layout>
