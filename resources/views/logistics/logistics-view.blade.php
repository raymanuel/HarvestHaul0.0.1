<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">
    <!-- Ambient glow decoration -->
    <div class="absolute top-0 right-1/4 w-96 h-96 rounded-full bg-[#3A7D44]/5 blur-[120px] pointer-events-none z-0"></div>
    <div class="absolute top-1/3 left-1/3 w-[500px] h-[500px] rounded-full bg-[#1F4D25]/5 blur-[150px] pointer-events-none z-0"></div>

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-8 pt-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 px-3 py-1 rounded-full border border-[#3A7D44]/20">Logistics Portal</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">
                        Welcome back, {{ Auth::user()->name }}
                        @if(Auth::user()->logisticsProfile?->company_name)
                            <span class="text-lg font-medium text-slate-500 dark:text-slate-400 block sm:inline sm:ml-2">| {{ Auth::user()->logisticsProfile->company_name }}</span>
                        @endif
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Logistics Console — Manage fleet, drivers, and coordinate consolidated haul routes.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold font-mono text-slate-400 dark:text-slate-500">{{ now()->format('l, M d, Y') }}</span>
                </div>
            </div>
        </header>

        {{-- PENDING VERIFICATION BANNER --}}
        @if (!Auth::user()->logisticsProfile?->is_verified)
            <div class="mb-8 bg-gradient-to-r from-amber-500/10 via-amber-600/5 to-transparent border border-amber-500/20 rounded-3xl p-6 shadow-sm flex items-start gap-4 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-32 h-32 bg-amber-500/5 rounded-full blur-2xl group-hover:scale-150 transition-all duration-700"></div>
                <div class="w-12 h-12 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0 text-xl shadow-inner select-none">⏳</div>
                <div>
                    <h3 class="text-base font-bold text-amber-800 dark:text-amber-300 heading-font">Account Pending Verification</h3>
                    <p class="text-xs text-amber-700/95 dark:text-amber-400/90 mt-1.5 leading-relaxed max-w-3xl font-medium">
                        Your logistics partner profile is currently undergoing administrative compliance check. 
                        You will be authorized to register drivers and trucks once verified by the board.
                    </p>
                </div>
            </div>
        @endif

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-6 bg-[#3A7D44]/10 border border-[#3A7D44]/20 text-[#3A7D44] dark:text-[#3A7D44] rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#3A7D44] dark:text-[#3A7D44] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-500/10 border border-red-500/20 text-red-750 dark:text-red-400 rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
                <span class="w-6 h-6 rounded-full bg-red-500/20 flex items-center justify-center text-red-500 shrink-0 text-xs">⚠️</span>
                {{ session('error') }}
            </div>
        @endif

        {{-- ── LOGISTICS OVERVIEW STATS ── --}}
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Fleet & Capacity Monitor</h2>
            <span class="w-20 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

            {{-- Card 1: Active Trucks --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#1F4D25]/5 hover:border-[#1F4D25]/20 dark:hover:border-[#1F4D25]/20 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-[#1F4D25]/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#1F4D25]/10 border border-[#1F4D25]/15 flex items-center justify-center text-[#1F4D25] dark:text-[#1F4D25] shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10M13 16h6m-6 0H6m13 0a2 2 0 002-2v-4a1 1 0 00-1-1h-6.18c-.09-.27-.27-.49-.52-.61l-2.6-1.3a1 1 0 00-1.12.18l-1.6 1.6" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-[#1F4D25] dark:text-[#1F4D25] bg-[#1F4D25]/10 px-2 py-0.5 rounded border border-[#1F4D25]/10">Fleet size</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Registered Trucks</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ Auth::user()->logisticsProfile?->trucks()->count() ?? 0 }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">active trucks</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    @if (Auth::user()->logisticsProfile?->is_verified)
                        <a href="{{ route('logistics.vehicles.index') }}" class="text-[#3A7D44] dark:text-[#3A7D44] font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                            Manage Fleet <span>→</span>
                        </a>
                    @else
                        <span class="text-slate-350 dark:text-slate-650 font-bold text-xs select-none">Fleet Manager Locked</span>
                    @endif
                </div>
            </div>

            {{-- Card 2: Managed Drivers --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-violet-500/5 hover:border-violet-500/30 dark:hover:border-violet-500/30 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-violet-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-violet-500/10 border border-violet-500/15 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-violet-600 dark:text-violet-400 bg-violet-500/10 px-2 py-0.5 rounded border border-violet-500/10">Staff Registry</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Assigned Drivers</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ Auth::user()->logisticsProfile?->drivers()->count() ?? 0 }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">hired crew</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    @if (Auth::user()->logisticsProfile?->is_verified)
                        <a href="{{ route('logistics.drivers.index') }}" class="text-[#3A7D44] dark:text-[#3A7D44] font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                            Manage Drivers <span>→</span>
                        </a>
                    @else
                        <span class="text-slate-350 dark:text-slate-650 font-bold text-xs select-none">Drivers Manager Locked</span>
                    @endif
                </div>
            </div>

            {{-- Card 3: Active Haul Requests --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-[#3A7D44]/5 hover:border-[#3A7D44]/30 dark:hover:border-[#3A7D44]/30 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-[#3A7D44]/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-[#3A7D44]/10 border border-[#3A7D44]/15 flex items-center justify-center text-[#3A7D44] dark:text-[#3A7D44] shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 px-2 py-0.5 rounded border border-[#3A7D44]/10">Marketplace</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Active Harvest Lots</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ $activeHarvestCount }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">available lots</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    @if (Auth::user()->logisticsProfile?->is_verified)
                        <a href="{{ route('route.optimization') }}" class="text-[#3A7D44] dark:text-[#3A7D44] font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                            Launch Dispatch Board <span>→</span>
                        </a>
                    @else
                        <span class="text-slate-350 dark:text-slate-650 font-bold text-xs select-none">Dispatch Engine Locked</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── LIVE OPERATIONS WORKSPACE ── --}}
        @if (Auth::user()->logisticsProfile?->is_verified)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">

                {{-- Left 2/3: Available Leads & Dispatch Run lists --}}
                <div class="lg:col-span-2 space-y-8">

                    {{-- CARD 1: Marketplace Opportunities (Available Crops) --}}
                    <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font">Marketplace Opportunities</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Available unassigned harvest lots matching your logistical scope.</p>
                            </div>
                            <a href="{{ route('route.optimization') }}" class="bg-[#3A7D44] hover:bg-[#2E6336] dark:bg-[#3A7D44]/100 dark:hover:bg-[#3A7D44] text-white text-xs font-bold px-4 py-2.5 rounded-xl transition duration-200 shadow-sm flex items-center gap-1.5">
                                Launch Dispatch Board <span>→</span>
                            </a>
                        </div>

                        @if($availableHarvests->isEmpty())
                            <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl">
                                
                                <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">No unassigned harvests available</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Check back later when farmers list new crop lots in your operational region.</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead>
                                        <tr class="border-b border-slate-150 dark:border-slate-700/50">
                                            <th class="pb-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Farmer</th>
                                            <th class="pb-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Crop</th>
                                            <th class="pb-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-right">Quantity</th>
                                            <th class="pb-3 pl-4 md:pl-6 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Destination</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100/50 dark:divide-slate-700/30">
                                        @foreach($availableHarvests as $harvest)
                                            <tr class="group hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition duration-150">
                                                <td class="py-3.5 pr-4 whitespace-nowrap">
                                                    <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $harvest->farmer->name }}</div>
                                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                        {{ $harvest->farmer->farmerProfile->affiliation_type === 'cooperative' ? 'Coop Member' : 'Independent Farmer' }}
                                                    </div>
                                                </td>
                                                <td class="py-3.5 pr-4 whitespace-nowrap">
                                                    <div class="font-semibold text-slate-700 dark:text-slate-300 text-xs">{{ $harvest->crop->name ?? $harvest->crop_type }}</div>
                                                    <div class="text-[10px] text-slate-400 dark:text-slate-500">{{ $harvest->cropVariety->name ?? $harvest->variety ?? 'Standard' }}</div>
                                                </td>
                                                <td class="py-3.5 pr-4 text-right font-mono font-extrabold text-slate-700 dark:text-slate-350 text-xs">
                                                    {{ number_format($harvest->quantity_kg) }} kg
                                                </td>
                                                <td class="py-3.5 text-slate-500 dark:text-slate-400 text-xs font-semibold max-w-[150px] truncate">
                                                    {{ $harvest->destination_label }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- CARD 2: Active Dispatch Runs --}}
                    <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font mb-2">Active Dispatches</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Confirmed or on-road highway pooling runs.</p>

                        @if($activeDispatchRuns->isEmpty())
                            <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl">
                                <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center mx-auto mb-3 text-slate-400"><svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z" /></svg></div>
                                <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">No active runs dispatching</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Group available crops into pooling routes to start dispatching fleet.</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead>
                                        <tr class="border-b border-slate-150 dark:border-slate-700/50">
                                            <th class="pb-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Driver / Truck</th>
                                            <th class="pb-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Capacity load</th>
                                            <th class="pb-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-right">Total Payload</th>
                                            <th class="pb-3 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100/50 dark:divide-slate-700/30">
                                        @foreach($activeDispatchRuns as $run)
                                            <tr class="group hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition duration-150">
                                                <td class="py-3.5 pr-4 whitespace-nowrap">
                                                    <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $run->driver->name ?? 'Unassigned' }}</div>
                                                    <div class="font-mono text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                        {{ $run->truck->truck_name ?? 'Truck' }} ({{ $run->truck->plate_number ?? '—' }})
                                                    </div>
                                                </td>
                                                <td class="py-3.5 pr-4 whitespace-nowrap">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-20 bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                                            <div class="bg-[#3A7D44]/100 h-full" style="width: {{ min(100, $run->load_percentage) }}%"></div>
                                                        </div>
                                                        <span class="text-[10px] font-bold text-slate-550 dark:text-slate-400">{{ $run->load_percentage }}%</span>
                                                    </div>
                                                </td>
                                                <td class="py-3.5 pr-4 text-right font-mono font-extrabold text-slate-700 dark:text-slate-350 text-xs">
                                                    {{ number_format($run->total_kg) }} kg
                                                </td>
                                                <td class="py-3.5 text-center whitespace-nowrap">
                                                    <span class="text-[9px] font-extrabold uppercase tracking-widest px-2.5 py-1 rounded-lg border {{ $run->status === 'in_progress' ? 'text-[#1F4D25] dark:text-[#1F4D25] bg-[#1F4D25]/10 border-[#1F4D25]/15 animate-pulse' : 'text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 border-[#3A7D44]/15' }}">
                                                        {{ $run->status === 'in_progress' ? 'En Route' : 'Confirmed' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Right 1/3: Proposals Sent / Negotiations Queue --}}
                <div>
                    <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm h-full flex flex-col justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font mb-2">Sent Proposals</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Negotiations sent to farmers for route splitting consent.</p>

                            @if($latestProposals->isEmpty())
                                <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl my-auto">
                                    <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center mx-auto mb-3 text-slate-400"><svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg></div>
                                    <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">No pending proposals</p>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-550 mt-1">Proposals await farmer cost consensus before route confirmation.</p>
                                </div>
                            @else
                                <div class="space-y-4">
                                    @foreach($latestProposals as $proposal)
                                        <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-5 hover:border-amber-500/30 dark:hover:border-amber-500/20 transition-all duration-300">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-[9px] font-extrabold uppercase tracking-widest text-amber-700 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/10">Awaiting Consensus</span>
                                                <a href="{{ route('pooling.index') }}" class="text-[10px] font-bold text-amber-650 hover:underline">
                                                    Manage
                                                </a>
                                            </div>

                                            <div class="space-y-1.5 text-xs text-slate-550 dark:text-slate-400">
                                                <div class="flex justify-between font-medium">
                                                    <span>Pooled Farms:</span>
                                                    <span class="font-bold text-slate-755 dark:text-slate-300">{{ $proposal->farm_count }} stops</span>
                                                </div>
                                                <div class="flex justify-between font-medium">
                                                    <span>Combined Cargo:</span>
                                                    <span class="font-bold text-slate-755 dark:text-slate-300 font-mono">{{ number_format($proposal->total_kg) }} kg</span>
                                                </div>
                                                <div class="flex justify-between font-medium">
                                                    <span>Negotiated Price:</span>
                                                    <span class="font-extrabold text-slate-950 dark:text-white font-mono">₱{{ number_format($proposal->negotiated_price, 2) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        @else
            {{-- Account Pending Board Verification State --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-12 text-center shadow-sm max-w-4xl mx-auto mb-12">
                <div class="w-16 h-16 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0 text-3xl mx-auto mb-6 shadow-inner select-none">⏳</div>
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white heading-font tracking-tight">Logistics Features Locked</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-3 max-w-2xl mx-auto leading-relaxed">
                    Your logistics partner profile and business permit credentials are currently undergoing review by the board.
                    Interactive fleet tracking, routing, dispatch optimization, and cost negotiations will fully unlock once verification is approved.
                </p>
                <div class="mt-8">
                    <a href="{{ route('logistics.documents') }}" class="bg-[#3A7D44] hover:bg-[#2E6336] text-white text-sm font-bold px-6 py-3 rounded-xl transition shadow-md">
                        Review Compliance Documents
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
</x-layout>
