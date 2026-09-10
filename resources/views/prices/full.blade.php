<x-layout>
<div class="w-full max-w-7xl mx-auto pb-12">

    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-700 dark:hover:text-slate-400 mb-4 inline-block font-semibold transition">
            ← Back to Dashboard
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Market Prices</h1>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-center">
                <a href="http://www.bantaypresyo.da.gov.ph/tbl_veg.php" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 text-[11px] font-bold text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 hover:bg-[#16283C]/15 dark:bg-[#16283C]/10 dark:hover:bg-[#16283C]/15 px-4 py-2 rounded-xl hover:underline transition">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    Bantay Presyo
                </a>
            </div>
        </div>
    </header>

    <x-flash-success />

    <x-flash-error />

    <x-flash-warning />

    <x-market-prices-card :compact="false" />

</div>
</x-layout>
