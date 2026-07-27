<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">
    <x-ambient-glow color="brand" />

    <div class="relative z-10">
        <x-page-header
            portal="Buyer Portal"
            title="Welcome, {{ Auth::user()->name }}"
            subtitle="Browse crop posts, negotiate with farmers, and manage your purchase deals."
            :showDate="true"
        />

        <x-flash-error />

        <x-section-label title="Buyer Console Dashboard" />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <x-stat-card
                accent="brand"
                badge="Active"
                title="Active Negotiations"
                :value="$activeNegotiations->count()"
                unit="open deals"
                href="{{ route('buyer.negotiations') }}"
                linkText="View Negotiations"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </x-stat-card>

            <x-stat-card
                accent="brand-dark"
                badge="Closed"
                title="Completed Deals"
                :value="$completedDeals"
                unit="total purchases"
                href="{{ route('buyer.negotiations') }}"
                linkText="View History"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-stat-card>

            <x-stat-card
                accent="harvest"
                badge="Marketplace"
                title="Crop Board"
                :value="$recentPosts->count()"
                unit="available lots"
                href="{{ route('buyer.crop-board') }}"
                linkText="Browse All Posts"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                </svg>
            </x-stat-card>
        </div>

        <div class="mb-10">
            <x-section-label title="DA RFO12 Market Prices" />
            <x-market-prices-card :daPrices="$daPrices" :priceTrends="$priceTrends" :latestDate="$latestDaDate" :scraperStatus="$scraperStatus" />
        </div>
    </div>
</div>
</x-layout>
