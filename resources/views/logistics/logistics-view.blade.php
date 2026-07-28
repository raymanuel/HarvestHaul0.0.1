<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <div class="relative z-10">
        <x-page-header
            portal="Logistics Portal"
            title="Welcome back, {{ Auth::user()->name }}"
            subtitle="Logistics Console — Manage fleet, drivers, and coordinate consolidated haul routes."
            :showDate="true"
        >
            @if(Auth::user()->logisticsProfile?->company_name)
                <span class="text-lg font-medium text-slate-500 dark:text-slate-400 block sm:inline sm:ml-2">| {{ Auth::user()->logisticsProfile->company_name }}</span>
            @endif
        </x-page-header>

        @if (!Auth::user()->logisticsProfile?->is_verified)
            <div class="mb-8 bg-amber-50 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-900/30 rounded-2xl px-5 py-4 flex gap-3.5 items-start shadow-sm">
                <span class="text-amber-500 mt-0.5 select-none"><x-icon name="document" class="w-5 h-5" /></span>
                <div>
                    <p class="text-sm font-bold text-amber-850 dark:text-amber-300 heading-font">Pending Verification</p>
                    <p class="text-xs text-amber-750 dark:text-amber-400 mt-1 leading-relaxed font-medium">Your account is being reviewed. Driver and truck registration will be available once approved.</p>
                </div>
            </div>
        @endif

        <x-flash-success />
        <x-flash-error />

        <x-section-label title="Fleet & Capacity Monitor" />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <x-stat-card
                accent="brand-dark"
                badge="Fleet size"
                title="Registered Trucks"
                :value="Auth::user()->logisticsProfile?->trucks()->count() ?? 0"
                unit="active trucks"
                :locked="!Auth::user()->logisticsProfile?->is_verified"
                lockedText="Fleet Manager Locked"
                href="{{ route('logistics.vehicles.index') }}"
                linkText="Manage Fleet"
                :icon="'<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; fill=&quot;none&quot; viewBox=&quot;0 0 24 24&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;2&quot;><path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; d=&quot;M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z&quot; /><path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; d=&quot;M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10M13 16h6m-6 0H6m13 0a2 2 0 002-2v-4a1 1 0 00-1-1h-6.18c-.09-.27-.27-.49-.52-.61l-2.6-1.3a1 1 0 00-1.12.18l-1.6 1.6&quot; /></svg>'"
            />

            <x-stat-card
                accent="harvest"
                badge="Staff Registry"
                title="Assigned Drivers"
                :value="Auth::user()->logisticsProfile?->drivers()->count() ?? 0"
                unit="hired crew"
                :locked="!Auth::user()->logisticsProfile?->is_verified"
                lockedText="Drivers Manager Locked"
                href="{{ route('logistics.drivers.index') }}"
                linkText="Manage Drivers"
                :icon="'<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; fill=&quot;none&quot; viewBox=&quot;0 0 24 24&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;2&quot;><path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; d=&quot;M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z&quot; /></svg>'"
            />

            <x-stat-card
                accent="brand"
                badge="Marketplace"
                title="Active Harvest Lots"
                :value="$activeHarvestCount"
                unit="available lots"
                :locked="!Auth::user()->logisticsProfile?->is_verified"
                lockedText="Dispatch Engine Locked"
                href="{{ route('route.optimization') }}"
                linkText="Launch Dispatch Board"
                :icon="'<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; fill=&quot;none&quot; viewBox=&quot;0 0 24 24&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;2&quot;><path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; d=&quot;M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z&quot; /><path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; d=&quot;M15 11a3 3 0 11-6 0 3 3 0 016 0z&quot; /></svg>'"
            />
        </div>

        @if (Auth::user()->logisticsProfile?->is_verified)
            <div class="mb-10">
                <x-section-label title="DA RFO12 Market Prices" />
                <x-market-prices-card :daPrices="$daPrices" :priceTrends="$priceTrends" :latestDate="$latestDaDate" :scraperStatus="$scraperStatus" />
            </div>
        @else
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-12 text-center shadow-sm max-w-4xl mx-auto mb-12">
                <div class="w-16 h-16 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0 mx-auto mb-6 shadow-inner select-none"><svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></div>
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white heading-font tracking-tight">Logistics Features Locked</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-3 max-w-2xl mx-auto leading-relaxed">
                    Your logistics partner profile and business permit credentials are currently undergoing review by the board.
                    Interactive fleet tracking, routing, dispatch optimization, and cost negotiations will fully unlock once verification is approved.
                </p>
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
