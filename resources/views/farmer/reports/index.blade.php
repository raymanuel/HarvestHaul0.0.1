<x-layout title="My Harvest Report — HarvestHaul">
    <x-page-header title="My Harvest Report" :showDate="true" />

    <x-report-date-filter :from="$from" :to="$to" :action="route('farmer.reports.index')" :csv-action="route('farmer.reports.csv')" :pdf-action="route('farmer.reports.pdf')" />

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
        <x-stat-card badge="Weight" title="Total Weight" :value="number_format($totalWeight, 1)" unit="kg" />
        <x-stat-card badge="Earnings" title="Total Earnings" :value="'₱'.number_format($totalEarnings, 2)" />
        <x-stat-card badge="Paid" title="Total Paid" :value="'₱'.number_format($totalPaid, 2)" />
        <x-stat-card badge="Balance" title="Outstanding" :value="'₱'.number_format($totalOutstanding, 2)" />
    </div>

    <x-card class="mb-6">
        <x-section-label title="Earnings by Crop" width="w-32" />
        @if($byCrop->isEmpty())
            <x-empty-state type="first-use" title="No confirmed harvests in this range" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Crop</th>
                            <th class="px-4 py-3 text-right">Harvests</th>
                            <th class="px-4 py-3 text-right">Weight</th>
                            <th class="px-4 py-3 text-right">Earnings</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($byCrop as $c)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $c['crop'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $c['count'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format($c['weight'], 2) }} kg</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">₱{{ number_format($c['earnings'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card>
        <x-section-label title="Confirmed Harvests" width="w-40" />
        @if($records->isEmpty())
            <x-empty-state type="first-use" title="No confirmed harvests in this range" description="Once your cooperative confirms a receiving record for a pickup, it appears here." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Crop</th>
                            <th class="px-4 py-3">Grade</th>
                            <th class="px-4 py-3 text-right">Weight</th>
                            <th class="px-4 py-3 text-right">Price/kg</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Paid</th>
                            <th class="px-4 py-3 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($records as $r)
                            <tr>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $r->confirmed_at?->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $r->crop?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $r->cropGrade?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format($r->actual_weight_kg, 2) }} kg</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">₱{{ number_format($r->buying_price_per_kg, 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-800 dark:text-slate-100">₱{{ number_format($r->total_amount, 2) }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">₱{{ number_format($r->totalPaid(), 2) }}</td>
                                <td class="px-4 py-3 text-right {{ $r->balanceDue() > 0 ? 'text-amber-700 dark:text-amber-400 font-semibold' : 'text-slate-600 dark:text-slate-300' }}">₱{{ number_format($r->balanceDue(), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>
