<x-layout>
@push('head')
    <style>
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>
@endpush
<div class="w-full max-w-7xl mx-auto pb-12">
    <h1 class="sr-only">Logistics Dashboard</h1>

    <div class="relative z-10">
        @if (!Auth::user()->logisticsProfile?->is_verified)
            <x-status-banner message="Your account is being reviewed. Driver and truck registration will be available once approved." />
        @endif

        @if(Auth::user()->logisticsProfile?->is_verified && is_null(Auth::user()->logisticsProfile?->latitude))
            <x-status-banner variant="missing-location" title="Set Your Office Location">
                Set your office coordinates in <a href="{{ route('profile.show') }}" class="underline font-bold">Profile Settings</a> to access full features.
            </x-status-banner>
        @endif

        <x-flash-success />
        <x-flash-error />

        @if (Auth::user()->logisticsProfile?->is_verified)
            @php
                $lp = Auth::user()->logisticsProfile;
                $companyName = $lp->company_name ?? '';
                $isCoop = $lp->isCooperative();
                $truckCount = $lp->trucks()->count();
                $driverCount = $lp->drivers()->count();
            @endphp
            <x-welcome-bar :name="Auth::user()->name">
                <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                    @if($companyName)
                        {{ $companyName }}
                        @if($isCoop)
                            <span class="text-[9px] font-bold uppercase tracking-widest text-brand dark:text-brand-light bg-brand/10 border border-brand/15 px-2 py-0.5 rounded ml-2">Cooperative</span>
                        @endif
                    @else
                        Logistics Partner
                    @endif
                </p>
            </x-welcome-bar>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <x-stat-card
                    title="Active Dispatches"
                    :value="$activeDispatchRuns->count()"
                    unit="runs"
                    href="{{ route('route.optimization') }}"
                    linkText="View Dispatches"
                />

                <x-stat-card
                    title="Available Pickups"
                    :value="$activeHarvestCount"
                    unit="harvests"
                    :href="$isCoop ? route('buyer.crop-board') : route('pooling.index')"
                    :linkText="$isCoop ? 'View Crop Board' : 'View Proposal Inbox'"
                />

                <x-stat-card
                    title="Transport Status"
                    value="{{ $availableTrucks }}/{{ $totalTrucks }}"
                    unit="trucks available"
                    href="{{ route('route.optimization') }}"
                    linkText="Manage Transport"
                >
                    <span class="text-[9px] font-semibold text-slate-500 dark:text-slate-400">{{ $availableDrivers }}/{{ $totalDrivers }} drivers free</span>
                </x-stat-card>

                <x-stat-card
                    title="Pending Invoices"
                    :value="$pendingInvoiceCount"
                    unit="invoices"
                    :badge="$overdueInvoiceCount > 0 ? $overdueInvoiceCount . ' overdue' : ''"
                    href="{{ route('pooling.cost-ledger.index') }}"
                    linkText="View Cost Ledger"
                />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <x-weather-card class="lg:col-span-1" :weather="$weatherData" />
                <x-market-prices-card class="lg:col-span-2" />
            </div>
        @else
            <div class="bg-surface-card dark:bg-surface-card-dark border border-slate-200/60 dark:border-dark-border rounded-3xl p-12 text-center shadow-sm max-w-4xl mx-auto mb-12">
                <div class="w-16 h-16 rounded-2xl bg-warning-bg border border-warning-border flex items-center justify-center text-warning-text shrink-0 mx-auto mb-6 shadow-inner select-none" aria-hidden="true"><svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div>
                <div class="mt-8">
                    <a href="{{ route('logistics.documents') }}" class="bg-brand hover:bg-brand-dark text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] text-sm font-bold px-6 py-3 rounded-xl transition shadow-md">
                        Review Compliance Documents
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
</x-layout>
