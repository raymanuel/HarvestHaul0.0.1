@props(['compact' => true])

<div {{ $attributes->merge(['class' => 'bg-surface-card dark:bg-surface-card-dark border border-slate-200/60 dark:border-dark-border rounded-2xl overflow-hidden']) }}>
    <div class="px-6 pt-6 pb-4 border-b border-slate-100 dark:border-dark-border">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white heading-font">DA RFO12 Market Prices</h2>
        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium mt-1">Current market prices for Region XII agricultural commodities. Updated daily by DA.</p>
    </div>

    <div class="px-6 py-5">
        <a href="http://www.bantaypresyo.da.gov.ph/tbl_veg.php" target="_blank" rel="noopener"
           class="group flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200/60 dark:border-dark-border bg-slate-50/50 dark:bg-dark-hover-bg hover:border-brand/30 dark:hover:border-gold-light/30 transition">
            <svg class="w-5 h-5 text-brand dark:text-brand-light shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <div class="flex-1 min-w-0">
                <p class="text-[11px] font-bold text-slate-800 dark:text-slate-200 group-hover:text-brand dark:group-hover:text-brand-light transition">View on Bantay Presyo</p>
                <p class="text-[10px] font-medium text-slate-500 dark:text-text-dark-muted">bantaypresyo.da.gov.ph</p>
            </div>
            <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 group-hover:text-brand dark:group-hover:text-brand-light shrink-0 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>
    </div>
</div>
