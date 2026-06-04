<x-layout>
<div class="w-full max-w-7xl mx-auto">

    <!-- Page Header -->
    <header class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white heading-font tracking-tight">Welcome back, Admin</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">System Orchestrator — Manage users, verifications, and platform activity</p>
            </div>
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1.5 rounded-lg border border-emerald-500/10 dark:border-emerald-500/20 self-start">Dashboard</span>
        </div>
    </header>

    @if (session('success'))
        <div class="mb-6 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-xl px-5 py-4 text-sm font-semibold flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- ── PLATFORM OVERVIEW STATS ── --}}
    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-4">Platform Overview</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">

        {{-- Total Users --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-950/20 border border-sky-100/50 dark:border-sky-800/30 flex items-center justify-center text-sky-600 dark:text-sky-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
            <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Total Users</h3>
            <p class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $totalUsers }}</p>
            <div class="flex flex-wrap gap-2 mt-3.5">
                <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-700 px-2 py-0.5 rounded">{{ $totalFarmers }} Farmers</span>
                <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-700 px-2 py-0.5 rounded">{{ $totalLogistics }} Logistics</span>
                <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-700 px-2 py-0.5 rounded">{{ $totalDrivers }} Drivers</span>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                <a href="{{ route('admin.users') }}" class="text-emerald-600 dark:text-emerald-400 font-semibold text-xs hover:underline transition inline-flex items-center gap-1">
                    Manage users <span>→</span>
                </a>
            </div>
        </div>

        {{-- Active Harvests --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 hover:shadow-md transition group">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100/50 dark:border-emerald-800/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>
            <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Active Harvests</h3>
            <p class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $activeHarvests }}</p>
            <div class="flex flex-wrap gap-2 mt-3.5">
                <span class="text-[10px] font-semibold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-800/40 px-2 py-0.5 rounded">{{ $pendingHarvests }} pending approval</span>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                <a href="{{ route('admin.harvests') }}" class="text-emerald-600 dark:text-emerald-400 font-semibold text-xs hover:underline transition inline-flex items-center gap-1">
                    View all listings <span>→</span>
                </a>
            </div>
        </div>

        {{-- Pending Verifications --}}
        <div class="bg-white dark:bg-slate-800 border rounded-2xl shadow-sm p-6 hover:shadow-md transition group {{ ($pendingFarmers > 0 || $pendingLogistics > 0) ? 'border-amber-200/70 dark:border-amber-800/80 shadow-amber-500/5' : 'border-slate-200/70 dark:border-slate-700/80' }}">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-100/50 dark:border-amber-800/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                @if($pendingFarmers > 0 || $pendingLogistics > 0)
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 px-2 py-0.5 rounded border border-amber-200/50 dark:border-amber-800/40 animate-pulse">Action Needed</span>
                @else
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30 px-2 py-0.5 rounded border border-emerald-100/50 dark:border-emerald-800/40">All Clear</span>
                @endif
            </div>
            <h3 class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Pending Verifications</h3>
            <p class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $pendingFarmers + $pendingLogistics }}</p>
            <div class="flex flex-wrap gap-2 mt-3.5">
                <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-700 px-2 py-0.5 rounded">{{ $pendingFarmers }} Farmers</span>
                <span class="text-[10px] font-semibold text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-700 px-2 py-0.5 rounded">{{ $pendingLogistics }} Logistics</span>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60 flex gap-4">
                <a href="{{ route('admin.farmers') }}" class="text-emerald-600 dark:text-emerald-400 font-semibold text-xs hover:underline transition inline-flex items-center gap-1">Farmers <span>→</span></a>
                <a href="{{ route('admin.logistics') }}" class="text-violet-600 dark:text-violet-400 font-semibold text-xs hover:underline transition inline-flex items-center gap-1">Logistics <span>→</span></a>
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
    <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200/70 dark:border-amber-850/50 rounded-2xl p-5 flex items-center justify-between flex-wrap gap-4 mb-8">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <div>
                <p class="text-sm font-bold text-amber-900 dark:text-amber-300 mb-1 heading-font">
                    {{ $totalPendingDocs }} Document{{ $totalPendingDocs > 1 ? 's' : '' }} Awaiting Review
                </p>
                <p class="text-xs text-amber-700 dark:text-amber-400 font-medium">
                    {{ $pendingFarmerDocs }} farmer {{ Str::plural('document', $pendingFarmerDocs) }}
                    &nbsp;·&nbsp;
                    {{ $pendingLogisticsDocs }} logistics {{ Str::plural('document', $pendingLogisticsDocs) }}
                </p>
            </div>
        </div>
        <div class="flex gap-3">
            @if($pendingFarmerDocs > 0)
                <a href="{{ route('admin.farmer-documents') }}"
                    class="bg-emerald-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-emerald-700 transition shadow-sm inline-flex items-center">
                    Review Farmer Docs
                </a>
            @endif
            @if($pendingLogisticsDocs > 0)
                <a href="{{ route('admin.logistics-documents') }}"
                    class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-slate-800 transition shadow-sm inline-flex items-center">
                    Review Logistics Docs
                </a>
            @endif
        </div>
    </div>
    @endif

    {{-- ── QUICK ACTIONS ── --}}
    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-4">Quick Actions</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">

        @php
            $quickActions = [
                ['route' => 'admin.farmer-documents', 'label' => 'Farmer Documents', 'badge' => $pendingFarmerDocs > 0 ? $pendingFarmerDocs . ' pending' : 'All reviewed', 'badgeType' => $pendingFarmerDocs > 0 ? 'amber' : 'slate'],
                ['route' => 'admin.logistics-documents', 'label' => 'Logistics Documents', 'badge' => $pendingLogisticsDocs > 0 ? $pendingLogisticsDocs . ' pending' : 'All reviewed', 'badgeType' => $pendingLogisticsDocs > 0 ? 'amber' : 'slate'],
                ['route' => 'admin.farmers', 'label' => 'Farmer Verification', 'badge' => $pendingFarmers > 0 ? $pendingFarmers . ' pending' : 'All verified', 'badgeType' => $pendingFarmers > 0 ? 'amber' : 'slate'],
                ['route' => 'admin.logistics', 'label' => 'Logistics Verification', 'badge' => $pendingLogistics > 0 ? $pendingLogistics . ' pending' : 'All verified', 'badgeType' => $pendingLogistics > 0 ? 'amber' : 'slate'],
                ['route' => 'admin.harvests', 'label' => 'Harvest Listings', 'badge' => $activeHarvests . ' active', 'badgeType' => 'slate'],
                ['route' => 'admin.crops.index', 'label' => 'Crop Registry', 'badge' => 'Manage crops', 'badgeType' => 'slate'],
                ['route' => 'admin.drivers', 'label' => 'Driver Accounts', 'badge' => $totalDrivers . ' registered', 'badgeType' => 'slate'],
                ['route' => 'admin.audit-logs', 'label' => 'Audit Logs', 'badge' => 'Full activity trail', 'badgeType' => 'slate'],
            ];
        @endphp

        @foreach($quickActions as $action)
            <a href="{{ route($action['route']) }}"
                class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl p-5 hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-md transition-all duration-200 group flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition heading-font">{{ $action['label'] }}</p>
                    <p class="text-[10px] font-semibold mt-1.5 {{ $action['badgeType'] === 'amber' ? 'text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 px-2 py-0.5 rounded border border-amber-200/40 dark:border-amber-800/40 inline-block' : 'text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 px-2 py-0.5 rounded border border-slate-200/60 dark:border-slate-700 inline-block' }}">{{ $action['badge'] }}</p>
                </div>
                <div class="text-slate-300 group-hover:text-emerald-600 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </a>
        @endforeach

    </div>

    {{-- ── RECENT ACTIVITY ── --}}
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Recent Activity</h2>
        <a href="{{ route('admin.audit-logs') }}" class="text-emerald-600 dark:text-emerald-400 font-bold text-xs hover:underline transition inline-flex items-center gap-1">View all <span>→</span></a>
    </div>

    <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden mb-10">
        @if($recentLogs->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-10 h-10 text-slate-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                <p class="text-slate-400 text-sm font-semibold">No activity recorded yet</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left" style="min-width:700px;">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40">
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Target</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Notes</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">By</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700/40">
                        @foreach($recentLogs as $log)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                            <td class="px-6 py-4">
                                <span class="bg-slate-50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider">
                                    {{ str_replace('_', ' ', $log->action) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-[10px] font-mono bg-slate-100/80 dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 px-2 py-0.5 rounded-md">
                                    {{ str_replace('_', ' ', $log->target_type) }} #{{ $log->target_id }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-medium max-w-[200px] truncate">{{ $log->notes ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-md bg-gradient-to-tr from-slate-100 to-slate-50 dark:from-slate-700 dark:to-slate-800 border border-slate-200/50 dark:border-slate-700 flex items-center justify-center text-[9px] font-extrabold text-slate-600 dark:text-slate-300 uppercase">{{ substr($log->admin->name ?? '—', 0, 2) }}</div>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $log->admin->name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-400 dark:text-slate-500 text-xs font-semibold whitespace-nowrap">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
</x-layout>
