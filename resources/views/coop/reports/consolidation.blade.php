<x-layout title="Consolidation Report — HarvestHaul">
    <x-page-header variant="back-link" title="Consolidation Report" :back-href="route('coop.reports.index')" back-label="← Back to Reports" />

    <x-report-date-filter :from="$from" :to="$to" :action="route('coop.reports.consolidation')" :csv-action="route('coop.reports.consolidation.csv')" :pdf-action="route('coop.reports.consolidation.pdf')" />

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-5 mb-8">
        <x-stat-card badge="Trips" title="Total Trips" :value="$totalTrips" />
        <x-stat-card badge="Stops" title="Total Stops" :value="$totalStops" />
        <x-stat-card badge="Load" title="Total Load" :value="number_format($totalLoad, 1)" unit="kg" />
        <x-stat-card badge="Utilization" title="Avg Utilization" :value="$avgUtilization" unit="%" />
    </div>

    <x-card>
        <x-section-label title="Consolidated Pickup Trips" width="w-44" />
        @if($rows->isEmpty())
            <x-empty-state type="first-use" title="No consolidated pickup trips in this range" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Truck</th>
                            <th class="px-4 py-3">Driver</th>
                            <th class="px-4 py-3 text-right">Stops</th>
                            <th class="px-4 py-3 text-right">Load</th>
                            <th class="px-4 py-3 text-right">Capacity</th>
                            <th class="px-4 py-3 text-right">Utilization</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($rows as $r)
                            <tr>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $r['job']->pickup_date?->format('M d, Y') }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $r['job']->truck?->plate_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $r['job']->deliveryPersonnel?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $r['stops'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format($r['load_kg'], 2) }} kg</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $r['capacity_kg'] ? number_format($r['capacity_kg'], 2).' kg' : '—' }}</td>
                                <td class="px-4 py-3 text-right {{ $r['utilization'] > 100 ? 'text-amber-700 dark:text-amber-400 font-semibold' : 'text-slate-600 dark:text-slate-300' }}">{{ $r['utilization'] }}%</td>
                                <td class="px-4 py-3"><x-badge :status="$r['job']->status" dot /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>
