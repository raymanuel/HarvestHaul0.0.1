<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <div class="relative z-10">
        <x-page-header
            portal="System Admin"
            title="Welcome back, Admin"
            subtitle="Orchestrator Console — Manage platform credentials, verify cooperative documents, and inspect activity trails."
            :showDate="true"
        />

        <x-flash-success />

        <x-section-label title="System Overview" />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <x-stat-card
                accent="brand-dark"
                badge="Active Accounts"
                title="Total Users"
                :value="$totalUsers"
                height="h-56"
                :subBadges="['Farmers' => $totalFarmers, 'Logistics' => $totalLogistics, 'Drivers' => $totalDrivers, 'Buyers' => $totalBuyers]"
                href="{{ route('admin.users') }}"
                linkText="Manage Users"
                :icon="'<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; fill=&quot;none&quot; viewBox=&quot;0 0 24 24&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;2&quot;><path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; d=&quot;M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z&quot; /></svg>'"
            />

            <x-stat-card
                accent="brand"
                badge="Marketplace"
                title="Active Harvests"
                :value="$activeHarvests"
                height="h-56"
                href="{{ route('admin.harvests') }}"
                linkText="View Posts"
                :icon="'<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; fill=&quot;none&quot; viewBox=&quot;0 0 24 24&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;2&quot;><path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; d=&quot;M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4&quot; /></svg>'"
            />

            <x-stat-card
                accent="amber-500"
                title="Pending Verifications"
                :value="$pendingFarmers + $pendingLogistics + $pendingBuyers"
                height="h-56"
                :subBadges="['Farmers' => $pendingFarmers, 'Logistics' => $pendingLogistics, 'Buyers' => $pendingBuyers]"
                :icon="'<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; fill=&quot;none&quot; viewBox=&quot;0 0 24 24&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;2&quot;><path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; d=&quot;M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z&quot; /></svg>'"
            >
                @if($pendingFarmers > 0 || $pendingLogistics > 0 || $pendingBuyers > 0)
                    <span class="text-[9px] font-extrabold uppercase tracking-widest text-amber-700 dark:text-amber-400 bg-amber-500/10 border border-amber-500/15 px-2 py-0.5 rounded-lg">Action Required</span>
                @else
                    <span class="text-[9px] font-extrabold uppercase tracking-widest text-brand dark:text-brand bg-brand/10 border border-brand/15 px-2 py-0.5 rounded-lg">Clear</span>
                @endif
            </x-stat-card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font mb-2">Account Verifications</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">New user accounts awaiting verification audit to access platform activities.</p>

                    @if($pendingFarmersList->isEmpty() && $pendingLogisticsList->isEmpty() && $pendingBuyersList->isEmpty())
                        <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl">
                            <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center mx-auto mb-3 text-slate-400"><svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg></div>
                            <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">All accounts verified</p>
                            <p class="text-[10px] text-slate-450 dark:text-slate-555 mt-1">No pending registrations awaiting approval.</p>
                        </div>
                    @else
                        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-1">
                            @foreach($pendingFarmersList as $farmer)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $farmer->name }}</span>
                                            <span class="text-[8px] font-extrabold uppercase tracking-widest text-brand dark:text-brand bg-brand/10 px-2 py-0.5 rounded border border-brand/10">Farmer</span>
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
                                            <button type="submit" class="bg-brand hover:bg-brand-dark text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition">
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

                            @foreach($pendingBuyersList as $buyer)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $buyer->name }}</span>
                                            <span class="text-[8px] font-extrabold uppercase tracking-widest text-brand-dark dark:text-brand bg-brand-50 px-2 py-0.5 rounded border border-brand-200">Buyer</span>
                                        </div>
                                        <p class="text-[10px] text-slate-455 dark:text-slate-450 mt-1">
                                            Email: {{ $buyer->email }}
                                        </p>
                                        <p class="text-[9px] text-slate-400 dark:text-slate-500 font-mono mt-0.5">
                                            Registered: {{ $buyer->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form action="{{ route('admin.buyers.verify', $buyer->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-brand hover:bg-brand-dark text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.buyers.reject', $buyer->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-red-500/10 hover:bg-red-500/20 text-red-650 dark:text-red-405 text-[10px] font-bold px-3 py-1.5 rounded-lg transition border border-red-500/10">
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
                                            <span class="text-[8px] font-extrabold uppercase tracking-widest text-harvest dark:text-harvest bg-harvest/10 px-2 py-0.5 rounded border border-harvest/10">Logistics</span>
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
                                            <button type="submit" class="bg-brand hover:bg-brand-dark text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition">
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

            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white heading-font mb-2">Pending Documents</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Compliance files and permit documents uploaded by participants for validation.</p>

                    @if($pendingFarmerDocsList->isEmpty() && $pendingLogisticsDocsList->isEmpty())
                        <div class="p-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl">
                            <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center mx-auto mb-3 text-slate-400"><svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg></div>
                            <p class="text-slate-455 dark:text-slate-400 text-sm font-semibold">All documents reviewed</p>
                            <p class="text-[10px] text-slate-450 dark:text-slate-555 mt-1">No uploaded files currently awaiting audit verification.</p>
                        </div>
                    @else
                        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-1">
                            @foreach($pendingFarmerDocsList as $doc)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $doc->document_type }}</span>
                                            <span class="text-[8px] font-extrabold uppercase tracking-widest text-brand dark:text-brand bg-brand/10 px-2 py-0.5 rounded border border-brand/10">Farmer</span>
                                        </div>
                                        <p class="text-[10px] text-slate-455 mt-1">
                                            User: {{ $doc->user->name ?? 'Unknown' }}
                                        </p>
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="text-[10px] text-brand-dark dark:text-brand hover:underline mt-1 font-bold inline-block">
                                            View Uploaded Document ↗
                                        </a>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form action="{{ route('admin.farmer-documents.approve', $doc->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="bg-brand hover:bg-brand-dark text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition">
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

                            @foreach($pendingLogisticsDocsList as $doc)
                                <div class="bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200/50 dark:border-slate-700 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ $doc->document_type }}</span>
                                            <span class="text-[8px] font-extrabold uppercase tracking-widest text-harvest dark:text-harvest bg-harvest/10 px-2 py-0.5 rounded border border-harvest/10">Logistics</span>
                                        </div>
                                        <p class="text-[10px] text-slate-455 mt-1 font-semibold">
                                            User: {{ $doc->user->name ?? 'Unknown' }}
                                        </p>
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="text-[10px] text-brand-dark dark:text-brand hover:underline mt-1 font-bold inline-block">
                                            View Uploaded Document ↗
                                        </a>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form action="{{ route('admin.logistics-documents.approve', $doc->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="bg-brand hover:bg-brand-dark text-white text-[10px] font-bold px-3 py-1.5 rounded-lg transition">
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

        <div class="mb-10">
            <x-section-label title="DA RFO12 Market Prices" />
            <x-market-prices-card :daPrices="$daPrices" :priceTrends="$priceTrends" :latestDate="$latestDaDate" :scraperStatus="$scraperStatus" />
        </div>

        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Security Audit Trail</h2>
            <a href="{{ route('admin.audit-logs') }}" class="text-brand dark:text-brand font-bold text-xs hover:underline transition inline-flex items-center gap-1">View all <span>→</span></a>
        </div>

        <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl shadow-sm overflow-hidden mb-10">
            @if($recentLogs->isEmpty())
                <div class="p-12 text-center">
                    <svg class="w-10 h-10 text-slate-200 dark:text-slate-650 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <p class="text-slate-400 text-sm font-semibold">No activity recorded yet</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-slate-150 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/60">
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-widest">Action</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-widest">Target</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-widest">Notes</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-widest">By</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-widest">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                            @foreach($recentLogs as $log)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-350 text-[9px] font-extrabold px-2.5 py-1 rounded-lg uppercase tracking-wider">
                                        {{ str_replace('_', ' ', $log->action) }}
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
                                <td class="px-4 py-3 text-slate-400 dark:text-slate-550 text-xs font-bold whitespace-nowrap">{{ $log->created_at->format('M d, Y h:i A') }}</td>
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
