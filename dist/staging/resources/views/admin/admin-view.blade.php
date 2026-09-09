<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">
    <h1 class="sr-only">Admin Dashboard</h1>

    <div class="relative z-10">
        <x-flash-success />
        <x-flash-error />

        @php
            $totalPendingDocs = $pendingFarmerDocsList->count() + $pendingLogisticsDocsList->count();
            $totalPendingVerifications = $pendingFarmers + $pendingLogistics + $pendingBuyers;
        @endphp

        <x-welcome-bar message="Welcome back, Admin." :subtitle="$totalPendingVerifications > 0 ? $totalPendingVerifications . ' item' . ($totalPendingVerifications > 1 ? 's' : '') . ' need attention' : ''" />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <x-stat-card
                title="Pending Verifications"
                :value="$totalPendingVerifications"
                :subBadges="['Farmers' => $pendingFarmers, 'Logistics' => $pendingLogistics, 'Buyers' => $pendingBuyers]"
                href="{{ route('admin.farmers') }}"
                linkText="Review Accounts"
            >
                @if($totalPendingVerifications > 0)
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-warning-text bg-warning-bg border border-warning-border px-2 py-0.5 rounded-md">Action Required</span>
                @endif
            </x-stat-card>

            <x-stat-card
                title="Pending Documents"
                :value="$totalPendingDocs"
                href="{{ route('admin.farmer-documents') }}"
                linkText="Review Documents"
            >
                @if($totalPendingDocs > 0)
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-warning-text bg-warning-bg border border-warning-border px-2 py-0.5 rounded-md">Action Required</span>
                @endif
            </x-stat-card>

            <x-stat-card
                title="Platform Activity"
                value="{{ $monthlyHarvests + $monthlyJobs + $monthlyDeals }}"
                unit="this month"
                href="{{ route('admin.analytics') }}"
                linkText="View Analytics"
            >
                <div class="flex flex-wrap gap-1.5 mt-3">
                    <span class="text-[9px] font-semibold text-text-muted bg-slate-100 dark:text-text-dark-muted dark:bg-dark-hover-bg border border-slate-200/60 dark:border-dark-border px-2 py-0.5 rounded-md">{{ $monthlyHarvests }} harvests</span>
                    <span class="text-[9px] font-semibold text-text-muted bg-slate-100 dark:text-text-dark-muted dark:bg-dark-hover-bg border border-slate-200/60 dark:border-dark-border px-2 py-0.5 rounded-md">{{ $monthlyJobs }} jobs</span>
                    <span class="text-[9px] font-semibold text-text-muted bg-slate-100 dark:text-text-dark-muted dark:bg-dark-hover-bg border border-slate-200/60 dark:border-dark-border px-2 py-0.5 rounded-md">{{ $monthlyDeals }} deals</span>
                </div>
            </x-stat-card>
        </div>

        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-gold-600 dark:text-gold-light">Activity Log</h2>
            <a href="{{ route('admin.audit-logs') }}" class="text-brand dark:text-gold-light font-bold text-xs hover:underline transition inline-flex items-center gap-1">View all <span aria-hidden="true">→</span></a>
        </div>

        <div class="bg-surface-card dark:bg-surface-card-dark border border-slate-200/60 dark:border-dark-border rounded-3xl shadow-sm overflow-hidden mb-10">
            @if($recentLogs->isEmpty())
                <div class="p-12 text-center">
                    <svg class="w-10 h-10 text-slate-200 dark:text-slate-700 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <p class="text-slate-400 text-sm font-semibold">No activity recorded yet</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left" aria-label="Activity log">
                        <thead>
                            <tr class="border-b border-slate-200/60 dark:border-dark-border bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="px-4 py-3 text-[10px] font-bold text-text-muted dark:text-text-dark-muted uppercase tracking-widest">Action</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-text-muted dark:text-text-dark-muted uppercase tracking-widest">Target</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-text-muted dark:text-text-dark-muted uppercase tracking-widest">Notes</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-text-muted dark:text-text-dark-muted uppercase tracking-widest">By</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-text-muted dark:text-text-dark-muted uppercase tracking-widest">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                            @foreach($recentLogs as $log)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-400 text-[9px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider">
                                        {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-[10px] font-mono bg-slate-100/80 dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 px-2.5 py-0.5 rounded border border-slate-200 dark:border-slate-700/40">
                                        {{ str_replace('_', ' ', $log->target_type) }} #{{ $log->target_id }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-semibold max-w-[240px] truncate leading-normal">{{ $log->notes ?? '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md bg-gradient-to-tr from-slate-100 to-slate-50 dark:from-slate-700 dark:to-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[9px] font-extrabold text-slate-600 dark:text-slate-300 uppercase">{{ substr($log->admin->name ?? '—', 0, 2) }}</div>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $log->admin->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-600 text-xs font-bold whitespace-nowrap">{{ $log->created_at->format('M d, Y h:i A') }}</td>
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
