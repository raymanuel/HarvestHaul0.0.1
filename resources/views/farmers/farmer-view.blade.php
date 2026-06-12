<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <!-- Ambient glow decoration -->
    <div class="absolute top-0 right-1/4 w-96 h-96 rounded-full bg-emerald-500/5 blur-[120px] pointer-events-none z-0"></div>
    <div class="absolute top-1/3 left-1/3 w-[500px] h-[500px] rounded-full bg-teal-500/5 blur-[150px] pointer-events-none z-0"></div>

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-8 pt-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 dark:bg-emerald-400/10 px-3 py-1 rounded-full border border-emerald-500/20">Farmer Portal</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">Welcome, {{ Auth::user()->name }}</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Manage harvests, coordinate B2B highway pooling, and monitor active shipments.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold font-mono text-slate-400 dark:text-slate-500">{{ now()->format('l, M d, Y') }}</span>
                </div>
            </div>
        </header>

        {{-- PENDING VERIFICATION BANNER --}}
        @if (!Auth::user()->farmerProfile?->is_verified)
            <div class="mb-8 bg-gradient-to-r from-amber-500/10 via-amber-600/5 to-transparent border border-amber-500/20 rounded-3xl p-6 shadow-sm flex items-start gap-4 relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-32 h-32 bg-amber-500/5 rounded-full blur-2xl group-hover:scale-150 transition-all duration-700"></div>
                <div class="w-12 h-12 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0 text-xl shadow-inner select-none">⏳</div>
                <div>
                    <h3 class="text-base font-bold text-amber-800 dark:text-amber-300 heading-font">Account Pending Board Verification</h3>
                    <p class="text-xs text-amber-700/95 dark:text-amber-400/90 mt-1.5 leading-relaxed max-w-3xl font-medium">
                        Your submitted credentials and cooperative licensing records are currently undergoing verification audit. 
                        Full route pooling capabilities and crop listings will unlock once verified.
                    </p>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-500/10 border border-red-500/20 text-red-650 dark:text-red-400 rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
                <span class="w-6 h-6 rounded-full bg-red-500/20 flex items-center justify-center text-red-500 shrink-0 text-xs">⚠️</span>
                {{ session('error') }}
            </div>
        @endif

        {{-- ── PLATFORM OVERVIEW STATS ── --}}
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Farmer Console Dashboard</h2>
            <span class="w-20 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

            {{-- Card 1: Active Harvests --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-500/5 hover:border-emerald-500/30 dark:hover:border-emerald-500/30 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-emerald-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.271.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.271.477-4.5 1.253" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/10">In Stock</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Active Harvest Listings</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ $activeHarvestsCount ?? 0 }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">active lots</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    <a href="{{ route('harvests.index') }}" class="text-emerald-600 dark:text-emerald-400 font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                        Manage Harvests <span>→</span>
                    </a>
                </div>
            </div>

            {{-- Card 2: Track Shipments --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-sky-500/5 hover:border-sky-500/30 dark:hover:border-sky-500/30 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-sky-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-sky-500/10 border border-sky-500/15 flex items-center justify-center text-sky-600 dark:text-sky-450 shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-sky-600 dark:text-sky-400 bg-sky-500/10 px-2 py-0.5 rounded border border-sky-500/10">In Transit</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Shipments En Route</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ $activeShipmentsCount ?? 0 }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">active runs</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    <a href="{{ route('tracking.index') }}" class="text-sky-600 dark:text-sky-400 font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                        View Live Map <span>→</span>
                    </a>
                </div>
            </div>

            {{-- CARD 3: B2B Pool Proposals --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-amber-500/5 hover:border-amber-500/30 dark:hover:border-amber-500/30 transition-all duration-300 group flex flex-col justify-between h-52 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-amber-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/15 flex items-center justify-center text-amber-600 dark:text-amber-450 shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-amber-700 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/10">Action Required</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Pooling Proposals</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2.5">
                        {{ $pendingProposalsCount ?? 0 }} <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">pending deals</span>
                    </p>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    <a href="{{ route('farmer.proposals') }}" class="text-amber-650 dark:text-amber-450 font-bold text-xs hover:underline transition inline-flex items-center gap-1.5 group-hover:translate-x-1 duration-200">
                        Review Cost Splits <span>→</span>
                    </a>
                </div>
            </div>

        </div>

        {{-- ── QUICK ACTIONS ── --}}
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Agricultural Workspace Tools</h2>
            <span class="w-20 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-12">

            @if (Auth::user()->farmerProfile?->is_verified)
                <a href="{{ route('harvests.create') }}"
                    class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:border-emerald-500 dark:hover:border-emerald-450 hover:shadow-xl hover:shadow-emerald-500/5 transition-all duration-300 group flex items-center justify-between gap-6">
                    <div class="space-y-2">
                        <p class="text-base font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition heading-font">Post New Harvest</p>
                        <p class="text-xs text-slate-500 dark:text-slate-450 leading-normal">Register yield weight and target drop-offs for highway matching.</p>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-750 px-2 py-0.5 rounded inline-block">Market Ingress</span>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-550/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform duration-200 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                </a>

                <a href="{{ route('farmer.proposals') }}"
                    class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:border-emerald-500 dark:hover:border-emerald-450 hover:shadow-xl hover:shadow-emerald-500/5 transition-all duration-300 group flex items-center justify-between gap-6">
                    <div class="space-y-2">
                        <p class="text-base font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition heading-font">Request Route Pooling</p>
                        <p class="text-xs text-slate-500 dark:text-slate-450 leading-normal">Combine small loads with highway neighbors to slash freight cost.</p>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-750 px-2 py-0.5 rounded inline-block">Coop Logis</span>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-550/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform duration-200 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                </a>

                <a href="{{ route('farmer.predictor') }}"
                    class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:border-violet-500 dark:hover:border-violet-500/50 hover:shadow-xl hover:shadow-violet-500/5 transition-all duration-300 group flex items-center justify-between gap-6">
                    <div class="space-y-2">
                        <p class="text-base font-bold text-slate-800 dark:text-slate-200 group-hover:text-violet-750 dark:group-hover:text-violet-400 transition heading-font">Smart Yield Predictor</p>
                        <p class="text-xs text-slate-500 dark:text-slate-450 leading-normal">Estimate crop maturity timelines and project logistics requirements.</p>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-750 px-2 py-0.5 rounded inline-block">Predictive ML</span>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-violet-500/10 border border-violet-550/10 text-violet-650 dark:text-violet-400 flex items-center justify-center shrink-0 group-hover:translate-x-1 transition-transform duration-200 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </a>
            @else
                <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200/50 dark:border-slate-700/80 rounded-3xl p-6 flex items-center justify-between gap-6 opacity-75 select-none">
                    <div class="space-y-2">
                        <p class="text-base font-bold text-slate-400 dark:text-slate-500 heading-font">Post New Harvest</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 leading-normal">Register yield weight and target drop-offs for highway matching.</p>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-amber-700 dark:text-amber-400 bg-amber-500/10 border border-amber-500/25 px-2.5 py-0.5 rounded-lg">Verification Pending Approval</span>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-900 text-slate-300 dark:text-slate-600 flex items-center justify-center shrink-0 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200/50 dark:border-slate-700/80 rounded-3xl p-6 flex items-center justify-between gap-6 opacity-75 select-none">
                    <div class="space-y-2">
                        <p class="text-base font-bold text-slate-400 dark:text-slate-500 heading-font">Request Route Pooling</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 leading-normal">Combine small loads with highway neighbors to slash freight cost.</p>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-amber-700 dark:text-amber-400 bg-amber-500/10 border border-amber-500/25 px-2.5 py-0.5 rounded-lg">Verification Pending Approval</span>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-900 text-slate-300 dark:text-slate-600 flex items-center justify-center shrink-0 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200/50 dark:border-slate-700/80 rounded-3xl p-6 flex items-center justify-between gap-6 opacity-75 select-none">
                    <div class="space-y-2">
                        <p class="text-base font-bold text-slate-400 dark:text-slate-500 heading-font">Smart Yield Predictor</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 leading-normal">Estimate crop maturity timelines and project logistics requirements.</p>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-amber-700 dark:text-amber-400 bg-amber-500/10 border border-amber-500/25 px-2.5 py-0.5 rounded-lg">Verification Pending Approval</span>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-900 text-slate-300 dark:text-slate-600 flex items-center justify-center shrink-0 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
</x-layout>
