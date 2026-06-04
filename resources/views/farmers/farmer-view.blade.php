<x-layout>
<div class="w-full max-w-7xl mx-auto">

    <!-- Page Header -->
    <header class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Welcome, {{ Auth::user()->name }}</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Farmer Console — Manage harvests, logistics pooling, and track shipments</p>
            </div>
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1.5 rounded-lg border border-emerald-500/10 dark:border-emerald-500/20 self-start">Farmer Portal</span>
        </div>
    </header>

    {{-- PENDING VERIFICATION BANNER --}}
    @if (!Auth::user()->farmerProfile?->is_verified)
        <div class="mb-6 bg-amber-50 dark:bg-amber-950/20 border border-amber-250/20 dark:border-amber-900/30 text-amber-700 dark:text-amber-400 rounded-2xl px-5 py-4 text-sm font-semibold flex items-start gap-3.5 shadow-sm">
            <span class="text-xl mt-0.5 select-none">⏳</span>
            <div>
                <p class="text-sm font-bold text-amber-800 dark:text-amber-300 heading-font">Account Pending Verification</p>
                <p class="text-xs text-amber-750 dark:text-amber-400 mt-1 leading-relaxed font-medium">
                    Your farmer profile is currently under administrative audit. 
                    You can post new harvest listings once your credentials and licenses are verified by the board.
                </p>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-950/20 border border-red-200/50 dark:border-red-900/30 text-red-700 dark:text-red-400 rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2 shadow-sm">
            <span class="text-xs">⚠️</span>
            {{ session('error') }}
        </div>
    @endif

    {{-- ── PLATFORM OVERVIEW STATS ── --}}
    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-4">Farmer Overview</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">

        {{-- Card 1: Active Harvests --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100/50 dark:border-emerald-800/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.271.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.271.477-4.5 1.253" />
                    </svg>
                </div>
            </div>
            <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Active Harvests</h3>
            <p class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
                {{ $activeHarvestsCount ?? 0 }} <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Items</span>
            </p>
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                <a href="{{ route('harvests.index') }}" class="text-emerald-600 dark:text-emerald-400 font-semibold text-xs hover:underline transition inline-flex items-center gap-1">
                    Manage harvests <span>→</span>
                </a>
            </div>
        </div>

        {{-- Card 2: Track Shipments --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-950/20 border border-sky-100/50 dark:border-sky-800/30 flex items-center justify-center text-sky-600 dark:text-sky-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>
            <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Track Shipments</h3>
            <p class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
                {{ $activeShipmentsCount ?? 0 }} <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Active</span>
            </p>
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                <a href="{{ route('tracking.index') }}" class="text-emerald-600 dark:text-emerald-400 font-semibold text-xs hover:underline transition inline-flex items-center gap-1">
                    View live map <span>→</span>
                </a>
            </div>
        </div>

        {{-- CARD 3: B2B Pool Proposals --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-100/50 dark:border-amber-800/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Pooling Proposals</h3>
            <p class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
                {{ $pendingProposalsCount ?? 0 }} <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Offers</span>
            </p>
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                <a href="{{ route('farmer.proposals') }}" class="text-emerald-600 dark:text-emerald-400 font-semibold text-xs hover:underline transition inline-flex items-center gap-1">
                    Review pooling rates <span>→</span>
                </a>
            </div>
        </div>

    </div>

    {{-- ── QUICK ACTIONS ── --}}
    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-4">Farmer Actions</h2>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10">

        @if (Auth::user()->farmerProfile?->is_verified)
            <a href="{{ route('harvests.create') }}"
                class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-md transition-all duration-200 group flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition heading-font">Post New Harvest</p>
                    <p class="text-[10px] font-semibold mt-1.5 text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-700 inline-block">Create marketplace listing</p>
                </div>
                <div class="text-slate-300 group-hover:text-emerald-600 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>

            <a href="{{ route('farmer.proposals') }}"
                class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-md transition-all duration-200 group flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition heading-font">Request Pooling</p>
                    <p class="text-[10px] font-semibold mt-1.5 text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-700 inline-block">Combine shipments and split cost</p>
                </div>
                <div class="text-slate-300 group-hover:text-emerald-600 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>

            {{-- Yield Predictor --}}
            <a href="{{ route('farmer.predictor') }}"
                class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 hover:border-violet-500 dark:hover:border-violet-500 hover:shadow-md transition-all duration-200 group flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-violet-700 dark:group-hover:text-violet-400 transition heading-font">Yield Predictor</p>
                    <p class="text-[10px] font-semibold mt-1.5 text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-700 inline-block">Estimate next harvest window</p>
                </div>
                <div class="text-slate-300 group-hover:text-violet-600 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>
        @else
            <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200/50 dark:border-slate-700/85 rounded-2xl p-5 flex items-center justify-between gap-4 select-none opacity-70">
                <div>
                    <p class="text-sm font-bold text-slate-400 dark:text-slate-500 heading-font">Post New Harvest</p>
                    <p class="text-[10px] font-bold mt-1.5 text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 px-2 py-0.5 rounded border border-amber-200/40 dark:border-amber-800/40 inline-block">Awaiting Verification Approval</p>
                </div>
                <div class="text-slate-300 dark:text-slate-600 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200/50 dark:border-slate-700/85 rounded-2xl p-5 flex items-center justify-between gap-4 select-none opacity-70">
                <div>
                    <p class="text-sm font-bold text-slate-400 dark:text-slate-500 heading-font">Request Pooling</p>
                    <p class="text-[10px] font-bold mt-1.5 text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 px-2 py-0.5 rounded border border-amber-200/40 dark:border-amber-800/40 inline-block">Awaiting Verification Approval</p>
                </div>
                <div class="text-slate-300 dark:text-slate-600 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>
        @endif

    </div>

</div>
</x-layout>
