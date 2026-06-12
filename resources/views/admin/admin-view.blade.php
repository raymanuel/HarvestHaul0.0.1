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

        {{-- ── PENDING DOCUMENT REVIEWS BANNER ── --}}
        @php
            $pendingFarmerDocs = \App\Models\FarmerDocument::where('status', 'pending')->count();
            $pendingLogisticsDocs = \App\Models\LogisticsDocument::where('status', 'pending')->count();
            $totalPendingDocs = $pendingFarmerDocs + $pendingLogisticsDocs;
        @endphp

        @if($totalPendingDocs > 0)
        <div class="bg-gradient-to-r from-amber-500/10 via-amber-600/5 to-transparent border border-amber-500/20 rounded-3xl p-6 shadow-sm flex items-center justify-between flex-wrap gap-4 mb-8">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0 text-xl shadow-inner select-none">📄</div>
                <div>
                    <h3 class="text-base font-bold text-amber-900 dark:text-amber-300 heading-font">
                        {{ $totalPendingDocs }} Document{{ $totalPendingDocs > 1 ? 's' : '' }} Awaiting Verification Review
                    </h3>
                    <p class="text-xs text-amber-700 dark:text-amber-400 font-semibold mt-1">
                        {{ $pendingFarmerDocs }} farmer {{ Str::plural('document', $pendingFarmerDocs) }}
                        &nbsp;·&nbsp;
                        {{ $pendingLogisticsDocs }} logistics {{ Str::plural('document', $pendingLogisticsDocs) }}
                    </p>
                </div>
            </div>
            <div class="flex gap-3">
                @if($pendingFarmerDocs > 0)
                    <a href="{{ route('admin.farmer-documents') }}"
                        class="bg-emerald-650 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center">
                        Verify Farmer Docs
                    </a>
                @endif
                @if($pendingLogisticsDocs > 0)
                    <a href="{{ route('admin.logistics-documents') }}"
                        class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center border border-slate-700">
                        Verify Logistics Docs
                    </a>
                @endif
            </div>
        </div>
        @endif

        {{-- ── QUICK ACTIONS ── --}}
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Quick Actions</h2>
            <span class="w-20 h-px bg-slate-200 dark:bg-slate-700/80"></span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

            @php
                $quickActions = [
                    ['route' => 'admin.farmer-documents', 'label' => 'Farmer Documents', 'badge' => $pendingFarmerDocs > 0 ? $pendingFarmerDocs . ' pending' : 'All clear', 'badgeType' => $pendingFarmerDocs > 0 ? 'amber' : 'slate'],
                    ['route' => 'admin.logistics-documents', 'label' => 'Logistics Documents', 'badge' => $pendingLogisticsDocs > 0 ? $pendingLogisticsDocs . ' pending' : 'All clear', 'badgeType' => $pendingLogisticsDocs > 0 ? 'amber' : 'slate'],
                    ['route' => 'admin.farmers', 'label' => 'Farmer Verification', 'badge' => $pendingFarmers > 0 ? $pendingFarmers . ' pending' : 'All verified', 'badgeType' => $pendingFarmers > 0 ? 'amber' : 'slate'],
                    ['route' => 'admin.logistics', 'label' => 'Logistics Verification', 'badge' => $pendingLogistics > 0 ? $pendingLogistics . ' pending' : 'All verified', 'badgeType' => $pendingLogistics > 0 ? 'amber' : 'slate'],
                    ['route' => 'admin.harvests', 'label' => 'Harvest Oversight', 'badge' => $activeHarvests . ' active', 'badgeType' => 'slate'],
                    ['route' => 'admin.crops.index', 'label' => 'Crop registry Manager', 'badge' => 'Admin tools', 'badgeType' => 'slate'],
                    ['route' => 'admin.drivers', 'label' => 'Driver Accounts', 'badge' => $totalDrivers . ' registered', 'badgeType' => 'slate'],
                    ['route' => 'admin.audit-logs', 'label' => 'Governance Logs', 'badge' => 'Security logs', 'badgeType' => 'slate'],
                ];
            @endphp

            @foreach($quickActions as $action)
                <a href="{{ route($action['route']) }}"
                    class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-5 hover:border-emerald-500 dark:hover:border-emerald-450 hover:shadow-xl hover:shadow-emerald-500/5 transition-all duration-300 group flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition heading-font">{{ $action['label'] }}</p>
                        <p class="text-[10px] font-semibold mt-1.5 {{ $action['badgeType'] === 'amber' ? 'text-amber-700 dark:text-amber-400 bg-amber-500/10 border border-amber-500/25 px-2 py-0.5 rounded-lg' : 'text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 px-2 py-0.5 rounded-lg' }}">{{ $action['badge'] }}</p>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-900 text-slate-350 group-hover:text-emerald-600 transition shrink-0 flex items-center justify-center border border-slate-100 dark:border-slate-750">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>
            @endforeach

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
