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
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">Welcome back, {{ Auth::user()->name }}</h1>
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
            <div class="mb-6 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600 dark:text-emerald-450 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-sky-500/5 hover:border-sky-500/30 dark:hover:border-sky-500/30 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-sky-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-sky-500/10 border border-sky-500/15 flex items-center justify-center text-sky-600 dark:text-sky-400 shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10M13 16h6m-6 0H6m13 0a2 2 0 002-2v-4a1 1 0 00-1-1h-6.18c-.09-.27-.27-.49-.52-.61l-2.6-1.3a1 1 0 00-1.12.18l-1.6 1.6" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-sky-600 dark:text-sky-400 bg-sky-500/10 px-2 py-0.5 rounded border border-sky-500/10">Fleet size</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Registered Trucks</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ Auth::user()->logisticsProfile?->trucks()->count() ?? 0 }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">active trucks</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    @if (Auth::user()->logisticsProfile?->is_verified)
                        <a href="{{ route('logistics.vehicles.index') }}" class="text-emerald-600 dark:text-emerald-400 font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
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
                        <a href="{{ route('logistics.drivers.index') }}" class="text-emerald-600 dark:text-emerald-400 font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                            Manage Drivers <span>→</span>
                        </a>
                    @else
                        <span class="text-slate-350 dark:text-slate-650 font-bold text-xs select-none">Drivers Manager Locked</span>
                    @endif
                </div>
            </div>

            {{-- Card 3: Active Haul Requests --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-500/5 hover:border-emerald-500/30 dark:hover:border-emerald-500/30 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-emerald-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center text-emerald-600 dark:text-emerald-450 shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-emerald-700 dark:text-emerald-450 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/10">Marketplace</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Active Harvest Lots</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ $activeHarvestCount }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">available lots</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    @if (Auth::user()->logisticsProfile?->is_verified)
                        <a href="{{ route('route.optimization') }}" class="text-emerald-600 dark:text-emerald-400 font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                            Launch Dispatch Board <span>→</span>
                        </a>
                    @else
                        <span class="text-slate-350 dark:text-slate-650 font-bold text-xs select-none">Dispatch Engine Locked</span>
                    @endif
                </div>
            </div>

        </div>

        {{-- ── PARTNER ACTIONS ── --}}
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Operations Panel</h2>
            <span class="w-20 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            {{-- Action 1: Route Optimization --}}
            <a href="{{ Auth::user()->logisticsProfile?->is_verified ? route('route.optimization') : '#' }}"
                class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 {{ Auth::user()->logisticsProfile?->is_verified ? 'hover:border-emerald-500 dark:hover:border-emerald-450 hover:shadow-xl hover:shadow-emerald-500/5 cursor-pointer' : 'cursor-not-allowed opacity-75' }} transition-all duration-350 group flex items-center justify-between gap-6">
                <div class="space-y-2">
                    <p class="text-base font-bold text-slate-800 dark:text-slate-200 {{ Auth::user()->logisticsProfile?->is_verified ? 'group-hover:text-emerald-700 dark:group-hover:text-emerald-400' : '' }} transition heading-font">Optimize Highway Routes</p>
                    @if(Auth::user()->logisticsProfile?->is_verified)
                        <p class="text-xs text-slate-500 dark:text-slate-450 leading-normal">Solve TSP stops to bundle farmers highway runs.</p>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-750 px-2 py-0.5 rounded inline-block">Dispatch Console</span>
                    @else
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-amber-700 dark:text-amber-400 bg-amber-500/10 px-2.5 py-0.5 rounded border border-amber-500/15">Awaiting Account Approval</span>
                    @endif
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-550/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform duration-200 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                </div>
            </a>

            {{-- Action 2: Pooling Proposals --}}
            <a href="{{ Auth::user()->logisticsProfile?->is_verified ? route('pooling.index') : '#' }}"
                class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 {{ Auth::user()->logisticsProfile?->is_verified ? 'hover:border-emerald-500 dark:hover:border-emerald-450 hover:shadow-xl hover:shadow-emerald-500/5 cursor-pointer' : 'cursor-not-allowed opacity-75' }} transition-all duration-350 group flex items-center justify-between gap-6">
                <div class="space-y-2">
                    <p class="text-base font-bold text-slate-800 dark:text-slate-200 {{ Auth::user()->logisticsProfile?->is_verified ? 'group-hover:text-emerald-700 dark:group-hover:text-emerald-400' : '' }} transition heading-font">Proposal Inbox</p>
                    @if(Auth::user()->logisticsProfile?->is_verified)
                        <p class="text-xs text-slate-500 dark:text-slate-450 leading-normal">Review negotiations and accept proposed highway rates.</p>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-750 px-2 py-0.5 rounded inline-block">Consensus Rooms</span>
                    @else
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-amber-700 dark:text-amber-400 bg-amber-500/10 px-2.5 py-0.5 rounded border border-amber-500/15">Awaiting Account Approval</span>
                    @endif
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-550/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform duration-200 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
            </a>

            {{-- Action 3: Business Compliance Documents --}}
            <a href="{{ route('logistics.documents') }}"
                class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:border-emerald-500 dark:hover:border-emerald-450 hover:shadow-xl hover:shadow-emerald-500/5 transition-all duration-300 group flex items-center justify-between gap-6">
                <div class="space-y-2">
                    <p class="text-base font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition heading-font">Compliance Permits</p>
                    <p class="text-xs text-slate-500 dark:text-slate-450 leading-normal">Submit and review official permit credentials and sec records.</p>
                    <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-750 px-2 py-0.5 rounded inline-block">SEC / DTI Permits</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-550/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform duration-200 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </a>

            {{-- Action 4: Add New Vehicle --}}
            <a href="{{ Auth::user()->logisticsProfile?->is_verified ? route('logistics.vehicles.create') : '#' }}"
                class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 {{ Auth::user()->logisticsProfile?->is_verified ? 'hover:border-emerald-500 dark:hover:border-emerald-450 hover:shadow-xl hover:shadow-emerald-500/5 cursor-pointer' : 'cursor-not-allowed opacity-75' }} transition-all duration-350 group flex items-center justify-between gap-6">
                <div class="space-y-2">
                    <p class="text-base font-bold text-slate-800 dark:text-slate-200 {{ Auth::user()->logisticsProfile?->is_verified ? 'group-hover:text-emerald-700 dark:group-hover:text-emerald-400' : '' }} transition heading-font">Add New Truck</p>
                    @if(Auth::user()->logisticsProfile?->is_verified)
                        <p class="text-xs text-slate-500 dark:text-slate-450 leading-normal">Register heavy haul containers, load capacity, and metadata.</p>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-750 px-2 py-0.5 rounded inline-block">Fleet Ingress</span>
                    @else
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-amber-700 dark:text-amber-400 bg-amber-500/10 px-2.5 py-0.5 rounded border border-amber-500/15">Awaiting Account Approval</span>
                    @endif
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-550/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform duration-200 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </a>

            {{-- Action 5: Fleet Predictor --}}
            @if(Auth::user()->logisticsProfile?->is_verified)
            <a href="{{ route('logistics.predictor') }}"
                class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:border-sky-500 dark:hover:border-sky-500 hover:shadow-xl hover:shadow-sky-500/5 transition-all duration-300 group flex items-center justify-between gap-6">
                <div class="space-y-2">
                    <p class="text-base font-bold text-slate-800 dark:text-slate-200 group-hover:text-sky-700 dark:group-hover:text-sky-400 transition heading-font">Fleet Predictor</p>
                    <p class="text-xs text-slate-500 dark:text-slate-450 leading-normal">ML model to forecast transport runs based on historic harvests.</p>
                    <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-750 px-2 py-0.5 rounded inline-block">Predictive ML</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-sky-500/10 border border-sky-550/10 text-sky-655 dark:text-sky-455 flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform duration-200 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </a>

            {{-- Action 6: Cost Ledger --}}
            <a href="{{ route('pooling.cost-ledger.index') }}"
                class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:border-emerald-500 dark:hover:border-emerald-450 hover:shadow-xl hover:shadow-emerald-500/5 transition-all duration-300 group flex items-center justify-between gap-6">
                <div class="space-y-2">
                    <p class="text-base font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition heading-font">Freight Cost Ledger</p>
                    <p class="text-xs text-slate-500 dark:text-slate-450 leading-normal">Track splits, proportional shares, and payment settlements.</p>
                    <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-750 px-2 py-0.5 rounded inline-block">Proportional Splits</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-550/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform duration-200 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
            </a>
            @endif

        </div>
    </div>
</div>
</x-layout>
