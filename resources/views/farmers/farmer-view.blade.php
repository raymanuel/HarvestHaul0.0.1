<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">
    <h1 class="sr-only">Farmer Dashboard</h1>

    <div class="relative z-10">
        @if (!Auth::user()->farmerProfile?->is_verified)
            <div class="mb-8 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-2xl px-5 py-4 flex gap-3.5 items-start shadow-sm">
                <span class="text-[var(--color-warning-text)] mt-0.5 select-none"><x-icon name="document" class="w-5 h-5" /></span>
                <div>
                    <p class="text-sm font-bold text-[var(--color-warning-text)] heading-font">Pending Verification</p>
                    <p class="text-xs text-[var(--color-warning-text)] mt-1 leading-relaxed font-medium">Your account is being reviewed. You'll have full access once approved.</p>
                </div>
            </div>
        @endif

        @if(Auth::user()->farmerProfile?->is_verified && is_null(Auth::user()->farmerProfile?->latitude))
        <div class="mb-6 p-5 rounded-2xl bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] text-[var(--color-warning-text)]">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-[10px] bg-[var(--color-warning-bg)] flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold mb-0.5">Set Your Farm Location</h3>
                    <p class="text-xs leading-relaxed font-medium">Set your farm coordinates in <a href="{{ route('profile.show') }}" class="underline font-bold">Profile Settings</a> to access full features.</p>
                </div>
            </div>
        </div>
        @endif

        <x-flash-success />
        <x-flash-error />

        <x-welcome-bar message="Welcome back, {{ Auth::user()->name }}." />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <x-stat-card
                accent="brand"
                title="Pending Proposals"
                :value="$pendingProposalsCount"
                unit="to review"
                href="{{ route('farmer.proposals') }}"
                linkText="Review Proposals"
            />

            <x-stat-card
                accent="brand"
                title="Active Harvests"
                :value="$activeHarvestsCount"
                unit="posts"
                href="{{ route('harvests.index') }}"
                linkText="Manage Harvests"
            />

            <x-stat-card
                accent="brand"
                title="In Transit"
                :value="$activeShipmentsCount"
                unit="shipments"
                href="{{ route('tracking.index') }}"
                linkText="View Live Map"
            />
        </div>

        <div class="mb-10">
            <x-market-prices-card :daPrices="$daPrices" :priceTrends="$priceTrends" :latestDate="$latestDaDate" :scraperStatus="$scraperStatus" />
        </div>
</div>
</div>
</x-layout>
