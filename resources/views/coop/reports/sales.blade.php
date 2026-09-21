<x-layout title="Sales Report — HarvestHaul">
    <x-page-header variant="back-link" title="Sales Report" :back-href="route('coop.reports.index')" back-label="← Back to Reports" />

    <x-report-date-filter :from="$from" :to="$to" :action="route('coop.reports.sales')" :csv-action="route('coop.reports.sales.csv')" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
        <x-stat-card badge="Volume" title="Total Weight" :value="number_format($totalKg, 2)" unit="kg" />
        <x-stat-card badge="Revenue" title="Total Revenue" :value="'₱'.number_format($totalRevenue, 2)" />
    </div>

    <x-card>
        <x-section-label title="Booked Orders" width="w-20" />
        @if($orders->isEmpty())
            <x-empty-state type="first-use" title="No booked orders in this range" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Buyer</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Weight</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($orders as $order)
                            <tr>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $order->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $order->reference }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $order->buyer?->name ?? '—' }}</td>
                                <td class="px-4 py-3"><x-badge :status="$order->status" dot /></td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format($order->total_kg, 2) }} kg</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-800 dark:text-slate-100">₱{{ number_format($order->total_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>
