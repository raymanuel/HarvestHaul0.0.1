<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">
    <h1 class="sr-only">Farmer Dashboard</h1>

    <div class="relative z-10">
        @if (!Auth::user()->farmerProfile?->is_verified)
            <div class="mb-8 bg-amber-50 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-900/30 rounded-2xl px-5 py-4 flex gap-3.5 items-start shadow-sm">
                <span class="text-amber-500 mt-0.5 select-none"><x-icon name="document" class="w-5 h-5" /></span>
                <div>
                    <p class="text-sm font-bold text-amber-850 dark:text-amber-300 heading-font">Pending Verification</p>
                    <p class="text-xs text-amber-750 dark:text-amber-400 mt-1 leading-relaxed font-medium">Your account is being reviewed. You'll have full access once approved.</p>
                </div>
            </div>
        @endif

        <x-flash-success />
        <x-flash-error />

        <x-welcome-bar message="Welcome back, {{ Auth::user()->name }}." />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <x-stat-card
                accent="harvest"
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
