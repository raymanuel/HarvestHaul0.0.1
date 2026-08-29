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
            <div class="mb-8 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-2xl px-5 py-4 flex gap-3.5 items-start shadow-sm">
                <span class="text-[var(--color-warning-text)] mt-0.5 select-none"><x-icon name="document" class="w-5 h-5" /></span>
                <div>
                    <p class="text-sm font-bold text-[var(--color-warning-text)] heading-font">Pending Verification</p>
                    <p class="text-xs text-[var(--color-warning-text)] mt-1 leading-relaxed font-medium">Your account is being reviewed. Driver and truck registration will be available once approved.</p>
                </div>
            </div>
        @endif

        @if(Auth::user()->logisticsProfile?->is_verified && is_null(Auth::user()->logisticsProfile?->latitude))
        <div class="mb-6 p-5 rounded-2xl bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] text-[var(--color-warning-text)]">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-[10px] bg-[var(--color-warning-bg)] flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold mb-0.5">Set Your Office Location</h3>
                    <p class="text-xs leading-relaxed font-medium">Set your office coordinates in <a href="{{ route('profile.show') }}" class="underline font-bold">Profile Settings</a> to access full features.</p>
                </div>
            </div>
        </div>
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
            <x-welcome-bar message="Welcome back, {{ Auth::user()->name }}.">
                <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mb-6">
                    @if($companyName)
                        {{ $companyName }}
                        @if($isCoop)
                            <span class="text-[9px] font-bold uppercase tracking-widest text-brand dark:text-brand-light bg-brand/10 border border-brand/15 px-2 py-0.5 rounded ml-2">Cooperative</span>
                        @endif
                    @else
                        Logistics Partner
                    @endif
                    <span class="mx-2 text-slate-300 dark:text-slate-600">|</span>
                    {{ $truckCount }} {{ Str::plural('truck', $truckCount) }}
                    <span class="mx-2 text-slate-300 dark:text-slate-600">|</span>
                    {{ $driverCount }} {{ Str::plural('driver', $driverCount) }}
                </p>
            </x-welcome-bar>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <x-stat-card
                    accent="harvest"
                    title="Pending Proposals"
                    :value="$latestProposals->count()"
                    unit="to review"
                    href="{{ route('pooling.index') }}"
                    linkText="Review Proposals"
                />

                <x-stat-card
                    accent="brand"
                    title="Available Pickups"
                    :value="$activeHarvestCount"
                    unit="harvests"
                    href="{{ route('route.optimization') }}"
                    linkText="Launch Dispatch Board"
                />

                <x-stat-card
                    accent="navy"
                    title="Active Dispatches"
                    :value="$activeDispatchRuns->count()"
                    unit="runs"
                    href="{{ route('route.optimization') }}"
                    linkText="View Dispatches"
                />
            </div>

            <div class="mb-10">
                <x-market-prices-card :daPrices="$daPrices" :priceTrends="$priceTrends" :latestDate="$latestDaDate" :scraperStatus="$scraperStatus" />
            </div>
        @else
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-12 text-center shadow-sm max-w-4xl mx-auto mb-12">
                <div class="w-16 h-16 rounded-2xl bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] flex items-center justify-center text-[var(--color-warning-text)] shrink-0 mx-auto mb-6 shadow-inner select-none" aria-hidden="true"><svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div>
                <div class="mt-8">
                    <a href="{{ route('logistics.documents') }}" class="bg-brand hover:bg-brand-dark text-white text-sm font-bold px-6 py-3 rounded-xl transition shadow-md">
                        Review Compliance Documents
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
</x-layout>
