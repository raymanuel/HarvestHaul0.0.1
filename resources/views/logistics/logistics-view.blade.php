<x-layout>
    <div class="w-full max-w-7xl mx-auto pb-12">
        <!-- Page Header -->
        <header class="mb-8 pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Welcome back, {{ Auth::user()->name }}</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Logistics Console — Manage fleet, drivers, and coordinate haul routes</p>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1.5 rounded-lg border border-emerald-500/10 dark:border-emerald-500/20 self-start">Logistics Portal</span>
            </div>
        </header>

        {{-- PENDING VERIFICATION BANNER --}}
        @if (!Auth::user()->logisticsProfile?->is_verified)
            <div class="mb-6 bg-amber-50 dark:bg-amber-950/20 border border-amber-250/20 dark:border-amber-900/30 text-amber-750 dark:text-amber-450 rounded-2xl px-5 py-4 flex gap-3.5 items-start shadow-sm">
                <span class="text-xl mt-0.5 select-none">⏳</span>
                <div>
                    <p class="text-sm font-bold text-amber-850 dark:text-amber-300 heading-font">Account Pending Verification</p>
                    <p class="text-xs text-amber-750 dark:text-amber-400 mt-1 leading-relaxed font-medium">
                        Your logistics partner profile is currently under administrative audit. 
                        You will be able to manage vehicles and register drivers once verified by the board.
                    </p>
                </div>
            </div>
        @endif

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-950/20 border border-red-200/50 dark:border-red-800/30 text-red-700 dark:text-red-400 rounded-xl px-5 py-4 text-sm font-medium flex items-center gap-2">
                <span class="text-xs">⚠️</span>
                {{ session('error') }}
            </div>
        @endif

        {{-- ── LOGISTICS OVERVIEW STATS ── --}}
        <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-4">Logistics Overview</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">

            {{-- Card 1: Active Trucks --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-950/20 border border-sky-100/50 dark:border-sky-800/30 flex items-center justify-center text-sky-600 dark:text-sky-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10M13 16h6m-6 0H6m13 0a2 2 0 002-2v-4a1 1 0 00-1-1h-6.18c-.09-.27-.27-.49-.52-.61l-2.6-1.3a1 1 0 00-1.12.18l-1.6 1.6" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Active Trucks</h3>
                <p class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
                    {{ Auth::user()->logisticsProfile?->trucks()->count() ?? 0 }} <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Vehicles</span>
                </p>
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                    @if (Auth::user()->logisticsProfile?->is_verified)
                        <a href="{{ route('logistics.vehicles.index') }}" class="text-emerald-600 dark:text-emerald-400 font-semibold text-xs hover:underline transition inline-flex items-center gap-1">
                            Manage vehicles <span>→</span>
                        </a>
                    @else
                        <span class="text-slate-350 dark:text-slate-600 font-semibold text-xs select-none">Manage vehicles →</span>
                    @endif
                </div>
            </div>

            {{-- Card 2: Managed Drivers --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-950/20 border border-violet-100/50 dark:border-violet-800/30 flex items-center justify-center text-violet-600 dark:text-violet-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Managed Drivers</h3>
                <p class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
                    {{ Auth::user()->logisticsProfile?->drivers()->count() ?? 0 }} <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Staff</span>
                </p>
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                    @if (Auth::user()->logisticsProfile?->is_verified)
                        <a href="{{ route('logistics.drivers.index') }}" class="text-emerald-600 dark:text-emerald-400 font-semibold text-xs hover:underline transition inline-flex items-center gap-1">
                            Manage drivers <span>→</span>
                        </a>
                    @else
                        <span class="text-slate-350 dark:text-slate-600 font-semibold text-xs select-none">Manage drivers →</span>
                    @endif
                </div>
            </div>

            {{-- Card 3: Active Haul Requests --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100/50 dark:border-emerald-800/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Active Haul Requests</h3>
                <p class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
                    {{ $activeHarvestCount }} <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Items</span>
                </p>
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                    @if (Auth::user()->logisticsProfile?->is_verified)
                        <a href="{{ route('route.optimization') }}" class="text-emerald-600 dark:text-emerald-400 font-semibold text-xs hover:underline transition inline-flex items-center gap-1">
                            View on map <span>→</span>
                        </a>
                    @else
                        <span class="text-slate-350 dark:text-slate-600 font-semibold text-xs select-none">View on map →</span>
                    @endif
                </div>
            </div>

        </div>

        {{-- ── PARTNER ACTIONS ── --}}
        <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-4">Partner Actions</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
            {{-- Action 1: Route Optimization --}}
            <a href="{{ Auth::user()->logisticsProfile?->is_verified ? route('route.optimization') : '#' }}"
                class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 {{ Auth::user()->logisticsProfile?->is_verified ? 'hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-md cursor-pointer' : 'cursor-not-allowed opacity-75' }} transition-all duration-200 group flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 {{ Auth::user()->logisticsProfile?->is_verified ? 'group-hover:text-emerald-700 dark:group-hover:text-emerald-400' : '' }} transition heading-font">Optimize Route Stops</p>
                    @if(Auth::user()->logisticsProfile?->is_verified)
                        <p class="text-[10px] font-semibold mt-1.5 text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-700 inline-block">Active engine</p>
                    @else
                        <p class="text-[10px] font-bold mt-1.5 text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 px-2 py-0.5 rounded border border-amber-200/40 dark:border-amber-800/40 inline-block uppercase">Pending approval</p>
                    @endif
                </div>
                <div class="{{ Auth::user()->logisticsProfile?->is_verified ? 'text-slate-300 group-hover:text-emerald-600' : 'text-slate-300' }} transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transform {{ Auth::user()->logisticsProfile?->is_verified ? 'group-hover:translate-x-1' : '' }} transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>

            {{-- Action 2: Pooling Proposals --}}
            <a href="{{ Auth::user()->logisticsProfile?->is_verified ? route('pooling.index') : '#' }}"
                class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 {{ Auth::user()->logisticsProfile?->is_verified ? 'hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-md cursor-pointer' : 'cursor-not-allowed opacity-75' }} transition-all duration-200 group flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 {{ Auth::user()->logisticsProfile?->is_verified ? 'group-hover:text-emerald-700 dark:group-hover:text-emerald-400' : '' }} transition heading-font">Pooling Proposals</p>
                    @if(Auth::user()->logisticsProfile?->is_verified)
                        <p class="text-[10px] font-semibold mt-1.5 text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-700 inline-block">Review B2B offers</p>
                    @else
                        <p class="text-[10px] font-bold mt-1.5 text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 px-2 py-0.5 rounded border border-amber-200/40 dark:border-amber-800/40 inline-block uppercase">Pending approval</p>
                    @endif
                </div>
                <div class="{{ Auth::user()->logisticsProfile?->is_verified ? 'text-slate-300 group-hover:text-emerald-600' : 'text-slate-300' }} transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transform {{ Auth::user()->logisticsProfile?->is_verified ? 'group-hover:translate-x-1' : '' }} transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>

            {{-- Action 3: Business Compliance Documents --}}
            <a href="{{ route('logistics.documents') }}"
                class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-md transition-all duration-200 group flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition heading-font">Compliance Docs</p>
                    <p class="text-[10px] font-semibold mt-1.5 text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-700 inline-block">Manage licenses &amp; permits</p>
                </div>
                <div class="text-slate-300 group-hover:text-emerald-600 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>

            {{-- Action 4: Add New Vehicle --}}
            <a href="{{ Auth::user()->logisticsProfile?->is_verified ? route('logistics.vehicles.create') : '#' }}"
                class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 {{ Auth::user()->logisticsProfile?->is_verified ? 'hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-md cursor-pointer' : 'cursor-not-allowed opacity-75' }} transition-all duration-200 group flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 {{ Auth::user()->logisticsProfile?->is_verified ? 'group-hover:text-emerald-700 dark:group-hover:text-emerald-400' : '' }} transition heading-font">Add New Vehicle</p>
                    @if(Auth::user()->logisticsProfile?->is_verified)
                        <p class="text-[10px] font-semibold mt-1.5 text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-700 inline-block">Register trucks</p>
                    @else
                        <p class="text-[10px] font-bold mt-1.5 text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 px-2 py-0.5 rounded border border-amber-200/40 dark:border-amber-800/40 inline-block uppercase">Pending approval</p>
                    @endif
                </div>
                <div class="{{ Auth::user()->logisticsProfile?->is_verified ? 'text-slate-300 group-hover:text-emerald-600' : 'text-slate-300' }} transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transform {{ Auth::user()->logisticsProfile?->is_verified ? 'group-hover:translate-x-1' : '' }} transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>

            {{-- Action 5: Fleet Predictor --}}
            @if(Auth::user()->logisticsProfile?->is_verified)
            <a href="{{ route('logistics.predictor') }}"
                class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 hover:border-sky-500 dark:hover:border-sky-500 hover:shadow-md transition-all duration-200 group flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-sky-700 dark:group-hover:text-sky-400 transition heading-font">Fleet Predictor</p>
                    <p class="text-[10px] font-semibold mt-1.5 text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-700 inline-block">Forecast trucks needed</p>
                </div>
                <div class="text-slate-300 group-hover:text-sky-600 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>

            {{-- Action 6: Cost Ledger --}}
            <a href="{{ route('pooling.cost-ledger.index') }}"
                class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-md transition-all duration-200 group flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition heading-font">Cost Ledger</p>
                    <p class="text-[10px] font-semibold mt-1.5 text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-700 inline-block">Per-farmer cost breakdown</p>
                </div>
                <div class="text-slate-300 group-hover:text-emerald-600 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>
            @endif

        </div>
    </div>
</x-layout>
