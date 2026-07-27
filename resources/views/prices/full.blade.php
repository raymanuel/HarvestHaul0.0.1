<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-650 dark:hover:text-slate-350 mb-4 inline-block font-semibold transition">
            ← Back to Dashboard
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Market Prices</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium">Average Daily Price Index of Agricultural Commodities in SOCCSKSARGEN Region</p>
            </div>
            <a href="https://rfo12.da.gov.ph" target="_blank" rel="noopener"
                class="inline-flex items-center gap-2 text-[11px] font-bold text-blue-500 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 border border-blue-200/50 dark:border-blue-700/30 px-4 py-2 rounded-xl hover:underline transition self-start sm:self-center">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                DA RFO12 Source
            </a>
        </div>
    </header>

    <x-market-prices-card
        :daPrices="$daPrices"
        :priceTrends="$priceTrends"
        :latestDate="$latestDate"
        :scraperStatus="$scraperStatus"
        :compact="false"
    />

</div>
</x-layout>
