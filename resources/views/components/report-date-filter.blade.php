@props(['from', 'to', 'action', 'csvAction', 'pdfAction' => null])

<form method="GET" action="{{ $action }}" class="flex flex-wrap items-end gap-3 mb-6">
    <div>
        <label for="from" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">From</label>
        <input type="date" name="from" id="from" value="{{ $from->toDateString() }}"
               class="border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white" />
    </div>
    <div>
        <label for="to" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">To</label>
        <input type="date" name="to" id="to" value="{{ $to->toDateString() }}"
               class="border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white" />
    </div>
    <x-button variant="secondary" size="sm">Filter</x-button>
    <a href="{{ $csvAction }}?from={{ $from->toDateString() }}&to={{ $to->toDateString() }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline pb-2.5">Download CSV</a>
    @if($pdfAction)
        <a href="{{ $pdfAction }}?from={{ $from->toDateString() }}&to={{ $to->toDateString() }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline pb-2.5">Download PDF</a>
    @endif
</form>
