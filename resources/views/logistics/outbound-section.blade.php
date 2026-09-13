<div class="bg-surface-card dark:bg-surface-card-dark border border-slate-200/60 dark:border-dark-border rounded-3xl p-6 shadow-sm">
    <h2 class="text-sm font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-4">Outbound Distribution</h2>
    <div class="divide-y divide-slate-100 dark:divide-slate-700/60">
        <div class="flex items-center justify-between py-3">
            <span class="text-sm font-semibold text-slate-600 dark:text-slate-400">Outbound active orders</span>
            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $outboundStats['active_orders'] }}</span>
        </div>
        <div class="flex items-center justify-between py-3">
            <span class="text-sm font-semibold text-slate-600 dark:text-slate-400">Completed outbound</span>
            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $outboundStats['completed_orders'] }}</span>
        </div>
        <div class="flex items-center justify-between py-3">
            <span class="text-sm font-semibold text-slate-600 dark:text-slate-400">Customers</span>
            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $outboundStats['customers'] }}</span>
        </div>
    </div>
</div>