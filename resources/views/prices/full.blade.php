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

    @if(session('warning'))
        <div class="mb-6 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] text-[var(--color-warning-text)] rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
            <span class="w-6 h-6 rounded-full bg-[var(--color-warning-bg)] flex items-center justify-center text-[var(--color-warning-text)] shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </span>
            <span class="flex-1">{{ session('warning') }}</span>
        </div>
    @endif

    <x-flash-error />

    <x-flash-warning />

    <x-market-prices-card :compact="false" />

</div>
</x-layout>
