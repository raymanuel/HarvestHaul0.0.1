<x-layout title="Procurement Report — HarvestHaul">
    <x-page-header variant="back-link" title="Procurement Report" :back-href="route('coop.reports.index')" back-label="← Back to Reports" />

    <x-report-date-filter :from="$from" :to="$to" :action="route('coop.reports.procurement')" :csv-action="route('coop.reports.procurement.csv')" :pdf-action="route('coop.reports.procurement.pdf')" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
        <x-stat-card badge="Volume" title="Total Weight" :value="number_format($totalWeight, 2)" unit="kg" />
        <x-stat-card badge="Spend" title="Total Spend" :value="'₱'.number_format($totalSpend, 2)" />
    </div>

    <x-card>
        <x-section-label title="Confirmed Purchases" width="w-20" />
        @if($records->isEmpty())
            <x-empty-state type="first-use" title="No confirmed procurement in this range" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Farmer</th>
                            <th class="px-4 py-3">Crop</th>
                            <th class="px-4 py-3">Grade</th>
                            <th class="px-4 py-3 text-right">Weight</th>
                            <th class="px-4 py-3 text-right">Price/kg</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($records as $record)
                            <tr>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $record->confirmed_at?->format('M d, Y') }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $record->farmer?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $record->crop?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $record->cropGrade?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format($record->actual_weight_kg, 2) }} kg</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">₱{{ number_format($record->buying_price_per_kg, 2) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-800 dark:text-slate-100">₱{{ number_format($record->total_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>
