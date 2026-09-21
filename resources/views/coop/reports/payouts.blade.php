<x-layout title="Farmer Payouts Report — HarvestHaul">
    <x-page-header variant="back-link" title="Farmer Payouts Report" :back-href="route('coop.reports.index')" back-label="← Back to Reports" />

    <x-report-date-filter :from="$from" :to="$to" :action="route('coop.reports.payouts')" :csv-action="route('coop.reports.payouts.csv')" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
        <x-stat-card badge="Payouts" title="Total Paid" :value="'₱'.number_format($totalPaid, 2)" />
        <x-stat-card badge="Count" title="Payments Recorded" :value="$payments->count()" />
    </div>

    <x-card>
        <x-section-label title="Payments" width="w-16" />
        @if($payments->isEmpty())
            <x-empty-state type="first-use" title="No payments recorded in this range" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Farmer</th>
                            <th class="px-4 py-3">Method</th>
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($payments as $payment)
                            <tr>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $payment->paid_at?->format('M d, Y') }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $payment->receivingRecord?->farmer?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ ucwords(str_replace('_', ' ', $payment->method)) }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $payment->reference ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-800 dark:text-slate-100">₱{{ number_format($payment->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>
