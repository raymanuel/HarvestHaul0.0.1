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
                    <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 dark:bg-emerald-400/10 px-3 py-1 rounded-full border border-emerald-500/20">System Admin</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">Welcome back, Admin</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Orchestrator Console — Manage platform credentials, verify cooperative documents, and inspect activity trails.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold font-mono text-slate-400 dark:text-slate-500">{{ now()->format('l, M d, Y') }}</span>
                </div>
            </div>
        </header>

        @if (session('success'))
            <div class="mb-6 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600 dark:text-emerald-450 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- ── PLATFORM OVERVIEW STATS ── --}}
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">System Overview</h2>
            <span class="w-20 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

            {{-- Total Users --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-sky-500/5 hover:border-sky-500/30 dark:hover:border-sky-500/30 transition-all duration-300 group flex flex-col justify-between h-56 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-sky-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-sky-500/10 border border-sky-500/15 flex items-center justify-center text-sky-600 dark:text-sky-400 shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-sky-600 dark:text-sky-400 bg-sky-500/10 px-2 py-0.5 rounded border border-sky-500/10">Active Accounts</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Total Users</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2">{{ $totalUsers }}</p>
                    <div class="flex flex-wrap gap-1.5 mt-3">
                        <span class="text-[9px] font-semibold text-slate-600 dark:text-slate-350 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 px-2 py-0.5 rounded-md">{{ $totalFarmers }} Farmers</span>
                        <span class="text-[9px] font-semibold text-slate-600 dark:text-slate-350 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 px-2 py-0.5 rounded-md">{{ $totalLogistics }} Logistics</span>
                        <span class="text-[9px] font-semibold text-slate-600 dark:text-slate-350 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 px-2 py-0.5 rounded-md">{{ $totalDrivers }} Drivers</span>
                    </div>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60">
                    <a href="{{ route('admin.users') }}" class="text-sky-600 dark:text-sky-400 font-bold text-xs hover:underline transition inline-flex items-center gap-1">
                        Manage Users <span>→</span>
                    </a>
                </div>
            </div>

            {{-- Active Harvests --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-500/5 hover:border-emerald-500/30 dark:hover:border-emerald-500/30 transition-all duration-300 group flex flex-col justify-between h-56 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-emerald-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/10">Marketplace</span>
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Active Harvests</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2">{{ $activeHarvests }}</p>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <span class="text-[9px] font-semibold text-amber-700 dark:text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-md">{{ $pendingHarvests }} pending approval</span>
                    </div>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60">
                    <a href="{{ route('admin.harvests') }}" class="text-emerald-600 dark:text-emerald-400 font-bold text-xs hover:underline transition inline-flex items-center gap-1">
                        View Listings <span>→</span>
                    </a>
                </div>
            </div>

            {{-- Pending Verifications --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border rounded-3xl p-6 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 group flex flex-col justify-between h-56 relative overflow-hidden {{ ($pendingFarmers > 0 || $pendingLogistics > 0) ? 'border-amber-250/30 dark:border-amber-900/30 shadow-amber-500/5' : 'border-slate-200/60 dark:border-slate-700/60' }}">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-amber-500/5 rounded-full group-hover:scale-150 transition-all duration-500"></div>
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/15 flex items-center justify-center text-amber-600 dark:text-amber-450 shrink-0 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        @if($pendingFarmers > 0 || $pendingLogistics > 0)
                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-amber-700 dark:text-amber-400 bg-amber-500/10 border border-amber-500/15 px-2 py-0.5 rounded-lg animate-pulse">Action Required</span>
                        @else
                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-emerald-600 dark:text-emerald-450 bg-emerald-500/10 border border-emerald-500/15 px-2 py-0.5 rounded-lg">Clear</span>
                        @endif
                    </div>
                    <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none">Pending Verifications</h3>
                    <p class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight heading-font mt-2">{{ $pendingFarmers + $pendingLogistics }}</p>
                    <div class="flex flex-wrap gap-1.5 mt-3">
                        <span class="text-[9px] font-semibold text-slate-650 dark:text-slate-350 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 px-2 py-0.5 rounded-md">{{ $pendingFarmers }} Farmers</span>
                        <span class="text-[9px] font-semibold text-slate-650 dark:text-slate-350 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 px-2 py-0.5 rounded-md">{{ $pendingLogistics }} Logistics</span>
                    </div>
                </div>
                <div class="pt-4 border-t border-slate-100 dark:border-slate-700/60 flex gap-4">
                    <a href="{{ route('admin.farmers') }}" class="text-emerald-650 dark:text-emerald-400 font-bold text-xs hover:underline transition inline-flex items-center gap-1">Farmers <span>→</span></a>
                    <a href="{{ route('admin.logistics') }}" class="text-violet-650 dark:text-violet-400 font-bold text-xs hover:underline transition inline-flex items-center gap-1">Logistics <span>→</span></a>
                </div>
            </div>
        </div>

        {{-- ── ACTION QUEUES ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">

            {{-- COLUMN 1: Account Verifications Queue --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font mb-2">Account Verifications</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">New user accounts awaiting verification audit to access platform activities.</p>

                    @if($pendingFarmersList->isEmpty() && $pendingLogisticsList->isEmpty())
                        <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl">
                            <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center mx-auto mb-3 text-slate-400">🛡️</div>
                            <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">All accounts verified</p>
                            <p class="text-[10px] text-slate-450 dark:text-slate-555 mt-1">No pending registrations awaiting approval.</p>
                        </div>
                    @else
                        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-1">
                            {{-- Farmers --}}
                            @foreach($pendingFarmersList as $farmer)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-750 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $farmer->name }}</span>
                                            <span class="text-[8px] font-extrabold uppercase tracking-widest text-emerald-750 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/10">Farmer</span>
                                        </div>
                                        <p class="text-[10px] text-slate-455 dark:text-slate-450 mt-1">
                                            Location: {{ $farmer->farmerProfile->farm_location ?? 'Not set' }}
                                        </p>
                                        <p class="text-[9px] text-slate-400 dark:text-slate-500 font-mono mt-0.5">
                                            Registered: {{ $farmer->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form action="{{ route('admin.farmers.verify', $farmer->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.farmers.reject', $farmer->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-red-500/10 hover:bg-red-500/20 text-red-650 dark:text-red-405 text-[10px] font-bold px-3 py-1.5 rounded-lg transition border border-red-500/10">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Logistics Partners --}}
                            @foreach($pendingLogisticsList as $partner)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-750 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $partner->name }}</span>
                                            <span class="text-[8px] font-extrabold uppercase tracking-widest text-violet-750 dark:text-violet-400 bg-violet-500/10 px-2 py-0.5 rounded border border-violet-500/10">Logistics</span>
                                        </div>
                                        <p class="text-[10px] text-slate-455 dark:text-slate-455 mt-1 font-semibold">
                                            Company: {{ $partner->logisticsProfile->company_name ?? 'Not set' }}
                                        </p>
                                        <p class="text-[9px] text-slate-400 dark:text-slate-500 font-mono mt-0.5">
                                            Registered: {{ $partner->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form action="{{ route('admin.logistics.verify', $partner->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.logistics.reject', $partner->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-red-500/10 hover:bg-red-500/20 text-red-650 dark:text-red-405 text-[10px] font-bold px-3 py-1.5 rounded-lg transition border border-red-500/10">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- COLUMN 2: Pending Documents Review Queue --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font mb-2">Pending Documents</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Compliance files and permit documents uploaded by participants for validation.</p>

                    @if($pendingFarmerDocsList->isEmpty() && $pendingLogisticsDocsList->isEmpty())
                        <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl">
                            <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center mx-auto mb-3 text-slate-400">📄</div>
                            <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">All documents reviewed</p>
                            <p class="text-[10px] text-slate-450 dark:text-slate-555 mt-1">No uploaded files currently awaiting audit verification.</p>
                        </div>
                    @else
                        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-1">
                            {{-- Farmer Documents --}}
                            @foreach($pendingFarmerDocsList as $doc)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-750 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $doc->document_type }}</span>
                                            <span class="text-[8px] font-extrabold uppercase tracking-widest text-emerald-750 dark:text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/10">Farmer</span>
                                        </div>
                                        <p class="text-[10px] text-slate-455 mt-1">
                                            User: {{ $doc->user->name ?? 'Unknown' }}
                                        </p>
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="text-[10px] text-sky-600 dark:text-sky-455 hover:underline mt-1 font-bold inline-block">
                                            View Uploaded Document ↗
                                        </a>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form action="{{ route('admin.farmer-documents.approve', $doc->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.farmer-documents.reject', $doc->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="bg-red-500/10 hover:bg-red-500/20 text-red-650 dark:text-red-405 text-[10px] font-bold px-3 py-1.5 rounded-lg transition border border-red-500/10">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Logistics Documents --}}
                            @foreach($pendingLogisticsDocsList as $doc)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-750 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $doc->document_type }}</span>
                                            <span class="text-[8px] font-extrabold uppercase tracking-widest text-violet-755 dark:text-violet-400 bg-violet-500/10 px-2 py-0.5 rounded border border-violet-500/10">Logistics</span>
                                        </div>
                                        <p class="text-[10px] text-slate-455 mt-1 font-semibold">
                                            User: {{ $doc->user->name ?? 'Unknown' }}
                                        </p>
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="text-[10px] text-sky-600 dark:text-sky-455 hover:underline mt-1 font-bold inline-block">
                                            View Uploaded Document ↗
                                        </a>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form action="{{ route('admin.logistics-documents.approve', $doc->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.logistics-documents.reject', $doc->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="bg-red-500/10 hover:bg-red-500/20 text-red-650 dark:text-red-405 text-[10px] font-bold px-3 py-1.5 rounded-lg transition border border-red-500/10">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ── RECENT ACTIVITY ── --}}
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Security Audit Trail</h2>
            <a href="{{ route('admin.audit-logs') }}" class="text-emerald-600 dark:text-emerald-400 font-bold text-xs hover:underline transition inline-flex items-center gap-1">View all <span>→</span></a>
        </div>

        <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl shadow-sm overflow-hidden mb-10">
            @if($recentLogs->isEmpty())
                <div class="p-12 text-center">
                    <svg class="w-10 h-10 text-slate-200 dark:text-slate-650 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <p class="text-slate-400 text-sm font-semibold">No activity recorded yet</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left" style="min-width:700px;">
                        <thead>
                            <tr class="border-b border-slate-150 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-widest">Action</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-widest">Target</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-widest">Notes</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-widest">By</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-widest">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                            @foreach($recentLogs as $log)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-750 text-slate-700 dark:text-slate-350 text-[9px] font-extrabold px-2.5 py-1 rounded-lg uppercase tracking-wider">
                                        {{ str_replace('_', ' ', $log->action) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-[10px] font-mono bg-slate-100/80 dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 px-2.5 py-0.5 rounded border border-slate-200 dark:border-slate-700/40">
                                        {{ str_replace('_', ' ', $log->target_type) }} #{{ $log->target_id }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-semibold max-w-[240px] truncate leading-normal">{{ $log->notes ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md bg-gradient-to-tr from-slate-100 to-slate-50 dark:from-slate-700 dark:to-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[9px] font-extrabold text-slate-600 dark:text-slate-300 uppercase">{{ substr($log->admin->name ?? '—', 0, 2) }}</div>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $log->admin->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-400 dark:text-slate-550 text-xs font-bold whitespace-nowrap">{{ $log->created_at->format('M d, Y h:i A') }}</td>
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
