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
                accent="amber-500"
                title="Pending Verifications"
                :value="$totalPendingVerifications"
                :subBadges="['Farmers' => $pendingFarmers, 'Logistics' => $pendingLogistics, 'Buyers' => $pendingBuyers]"
                href="{{ route('admin.users') }}"
                linkText="Review Accounts"
            >
                @if($totalPendingVerifications > 0)
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] px-2 py-0.5 rounded-lg">Action Required</span>
                @else
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-brand dark:text-brand bg-brand/10 border border-brand/15 px-2 py-0.5 rounded-lg">Clear</span>
                @endif
            </x-stat-card>

            <x-stat-card
                accent="amber-500"
                title="Pending Documents"
                :value="$totalPendingDocs"
                href="{{ route('admin.users') }}"
                linkText="Review Documents"
            >
                @if($totalPendingDocs > 0)
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-[var(--color-warning-text)] bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] px-2 py-0.5 rounded-lg">Action Required</span>
                @else
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-brand dark:text-brand bg-brand/10 border border-brand/15 px-2 py-0.5 rounded-lg">Clear</span>
                @endif
            </x-stat-card>

            <x-stat-card
                accent="navy"
                title="Total Users"
                :value="$totalUsers"
                :subBadges="['Farmers' => $totalFarmers, 'Logistics' => $totalLogistics, 'Drivers' => $totalDrivers, 'Buyers' => $totalBuyers]"
                href="{{ route('admin.users') }}"
                linkText="Manage Users"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-800 dark:text-white heading-font mb-4">Account Verifications</h2>

                    @if($pendingFarmersList->isEmpty() && $pendingLogisticsList->isEmpty() && $pendingBuyersList->isEmpty())
                        <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl">
                            <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center mx-auto mb-3 text-slate-400" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg></div>
                            <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">All accounts verified</p>
                        </div>
                    @else
                        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-1">
                            @foreach($pendingFarmersList as $farmer)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $farmer->name }}</span>
                                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-brand dark:text-brand bg-brand/10 px-2 py-0.5 rounded border border-brand/10">Farmer</span>
                                        </div>
                                        <p class="text-[10px] text-slate-455 dark:text-slate-450 mt-1">
                                            Location: {{ $farmer->farmerProfile->farm_location ?? 'Not set' }}
                                        </p>
                                        <p class="text-[9px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">
                                            Registered: {{ $farmer->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form action="{{ route('admin.farmers.verify', $farmer->id) }}" method="POST">
                                            @csrf
                                            <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Approve?', text:'This action cannot be undone.', icon:'question', confirmText:'Yes, approve', confirmColor:'#16283C'})" class="bg-brand hover:bg-brand-dark text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition cursor-pointer">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.farmers.reject', $farmer->id) }}" method="POST">
                                            @csrf
                                            <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Reject?', text:'This account will be rejected.', icon:'warning', confirmText:'Yes, reject', confirmColor:'#ef4444'})" class="bg-[var(--color-error-bg)] hover:opacity-80 text-[var(--color-error-text)] text-[10px] font-bold px-3 py-1.5 rounded-lg transition border border-[var(--color-error-border)] cursor-pointer">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach

                            @foreach($pendingBuyersList as $buyer)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $buyer->name }}</span>
                                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-brand-dark dark:text-brand bg-brand-50 px-2 py-0.5 rounded border border-brand-200">Buyer</span>
                                        </div>
                                        <p class="text-[10px] text-slate-455 dark:text-slate-450 mt-1">
                                            Email: {{ $buyer->email }}
                                        </p>
                                        <p class="text-[9px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">
                                            Registered: {{ $buyer->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form action="{{ route('admin.buyers.verify', $buyer->id) }}" method="POST">
                                            @csrf
                                            <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Approve?', text:'This action cannot be undone.', icon:'question', confirmText:'Yes, approve', confirmColor:'#16283C'})" class="bg-brand hover:bg-brand-dark text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition cursor-pointer">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.buyers.reject', $buyer->id) }}" method="POST">
                                            @csrf
                                            <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Reject?', text:'This account will be rejected.', icon:'warning', confirmText:'Yes, reject', confirmColor:'#ef4444'})" class="bg-[var(--color-error-bg)] hover:opacity-80 text-[var(--color-error-text)] text-[10px] font-bold px-3 py-1.5 rounded-lg transition border border-[var(--color-error-border)] cursor-pointer">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach

                            @foreach($pendingLogisticsList as $partner)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $partner->name }}</span>
                                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-harvest dark:text-harvest bg-harvest/10 px-2 py-0.5 rounded border border-harvest/10">Logistics</span>
                                        </div>
                                        <p class="text-[10px] text-slate-455 dark:text-slate-455 mt-1 font-semibold">
                                            Company: {{ $partner->logisticsProfile->company_name ?? 'Not set' }}
                                        </p>
                                        <p class="text-[9px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">
                                            Registered: {{ $partner->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form action="{{ route('admin.logistics.verify', $partner->id) }}" method="POST">
                                            @csrf
                                            <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Approve?', text:'This action cannot be undone.', icon:'question', confirmText:'Yes, approve', confirmColor:'#16283C'})" class="bg-brand hover:bg-brand-dark text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition cursor-pointer">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.logistics.reject', $partner->id) }}" method="POST">
                                            @csrf
                                            <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Reject?', text:'This account will be rejected.', icon:'warning', confirmText:'Yes, reject', confirmColor:'#ef4444'})" class="bg-[var(--color-error-bg)] hover:opacity-80 text-[var(--color-error-text)] text-[10px] font-bold px-3 py-1.5 rounded-lg transition border border-[var(--color-error-border)] cursor-pointer">
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

            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-800 dark:text-white heading-font mb-4">Pending Documents</h2>

                    @if($pendingFarmerDocsList->isEmpty() && $pendingLogisticsDocsList->isEmpty())
                        <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl">
                            <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center mx-auto mb-3 text-slate-400" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg></div>
                            <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">All documents reviewed</p>
                        </div>
                    @else
                        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-1">
                            @foreach($pendingFarmerDocsList as $doc)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $doc->document_type }}</span>
                                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-brand dark:text-brand bg-brand/10 px-2 py-0.5 rounded border border-brand/10">Farmer</span>
                                        </div>
                                        <p class="text-[10px] text-slate-455 mt-1">
                                            User: {{ $doc->user->name ?? 'Unknown' }}
                                        </p>
                                        <a href="{{ route('files.show', ['type' => 'farmer-document', 'id' => $doc->id]) }}" target="_blank" rel="noopener" class="text-[10px] text-brand-dark dark:text-brand hover:underline mt-1 font-bold inline-block">
                                            View Uploaded Document <span aria-hidden="true">←—</span>
                                        </a>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form action="{{ route('admin.farmer-documents.approve', $doc->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Approve?', text:'This action cannot be undone.', icon:'question', confirmText:'Yes, approve', confirmColor:'#16283C'})" class="bg-brand hover:bg-brand-dark text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition cursor-pointer">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.farmer-documents.reject', $doc->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Reject?', text:'This account will be rejected.', icon:'warning', confirmText:'Yes, reject', confirmColor:'#ef4444'})" class="bg-[var(--color-error-bg)] hover:opacity-80 text-[var(--color-error-text)] text-[10px] font-bold px-3 py-1.5 rounded-lg transition border border-[var(--color-error-border)] cursor-pointer">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach

                            @foreach($pendingLogisticsDocsList as $doc)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $doc->document_type }}</span>
                                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-harvest dark:text-harvest bg-harvest/10 px-2 py-0.5 rounded border border-harvest/10">Logistics</span>
                                        </div>
                                        <p class="text-[10px] text-slate-455 mt-1 font-semibold">
                                            User: {{ $doc->user->name ?? 'Unknown' }}
                                        </p>
                                        <a href="{{ route('files.show', ['type' => 'logistics-document', 'id' => $doc->id]) }}" target="_blank" rel="noopener" class="text-[10px] text-brand-dark dark:text-brand hover:underline mt-1 font-bold inline-block">
                                            View Uploaded Document <span aria-hidden="true">←—</span>
                                        </a>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form action="{{ route('admin.logistics-documents.approve', $doc->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Approve?', text:'This action cannot be undone.', icon:'question', confirmText:'Yes, approve', confirmColor:'#16283C'})" class="bg-brand hover:bg-brand-dark text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition cursor-pointer">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.logistics-documents.reject', $doc->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button" onclick="swalConfirm(this.closest('form'), {title:'Reject?', text:'This account will be rejected.', icon:'warning', confirmText:'Yes, reject', confirmColor:'#ef4444'})" class="bg-[var(--color-error-bg)] hover:opacity-80 text-[var(--color-error-text)] text-[10px] font-bold px-3 py-1.5 rounded-lg transition border border-[var(--color-error-border)] cursor-pointer">
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

        <div class="mb-10">
            <x-market-prices-card :daPrices="$daPrices" :priceTrends="$priceTrends" :latestDate="$latestDaDate" :scraperStatus="$scraperStatus" />
        </div>

        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Activity Log</h2>
            <a href="{{ route('admin.audit-logs') }}" class="text-brand dark:text-brand font-bold text-xs hover:underline transition inline-flex items-center gap-1">View all <span aria-hidden="true">→</span></a>
        </div>

        <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl shadow-sm overflow-hidden mb-10">
            @if($recentLogs->isEmpty())
                <div class="p-12 text-center">
                    <svg class="w-10 h-10 text-slate-200 dark:text-slate-650 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <p class="text-slate-400 text-sm font-semibold">No activity recorded yet</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left" aria-label="Activity log">
                        <thead>
                            <tr class="border-b border-slate-150 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Action</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Target</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Notes</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-500 dark:text-slate-500 uppercase tracking-widest">By</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                            @foreach($recentLogs as $log)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-350 text-[9px] font-extrabold px-2.5 py-1 rounded-lg uppercase tracking-wider">
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
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-550 text-xs font-bold whitespace-nowrap">{{ $log->created_at->format('M d, Y h:i A') }}</td>
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
