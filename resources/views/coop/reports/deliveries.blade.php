<x-layout title="Fleet & Deliveries Report — HarvestHaul">
    <x-page-header variant="back-link" title="Fleet & Deliveries Report" :back-href="route('coop.reports.index')" back-label="← Back to Reports" />

    <x-report-date-filter :from="$from" :to="$to" :action="route('coop.reports.deliveries')" :csv-action="route('coop.reports.deliveries.csv')" :pdf-action="route('coop.reports.deliveries.pdf')" />

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <x-stat-card badge="Trips" title="Total Trips" :value="$totalTrips" />
        <x-stat-card badge="Distance" title="Total Distance" :value="number_format($totalDistance, 1)" unit="km" />
        <x-stat-card badge="Stops" title="Delivered / Failed" :value="$deliveredStops.' / '.$failedStops" />
    </div>

    <x-card class="mb-6">
        <x-section-label title="Vehicle Activity" width="w-20" />
        @if($vehicles->isEmpty())
            <x-empty-state type="first-use" title="No trips in this range" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Truck</th>
                            <th class="px-4 py-3 text-right">Trips</th>
                            <th class="px-4 py-3 text-right">Distance</th>
                            <th class="px-4 py-3 text-right">Stops</th>
                            <th class="px-4 py-3">Last Active</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($vehicles as $vehicle)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $vehicle['truck']?->plate_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $vehicle['trips'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format($vehicle['distance'], 1) }} km</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $vehicle['stops'] }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $vehicle['lastActive'] ? \Illuminate\Support\Carbon::parse($vehicle['lastActive'])->format('M d, Y') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card>
        <x-section-label title="Trips" width="w-16" />
        @if($jobs->isEmpty())
            <x-empty-state type="first-use" title="No trips in this range" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Truck</th>
                            <th class="px-4 py-3">Driver</th>
                            <th class="px-4 py-3 text-right">Stops</th>
                            <th class="px-4 py-3 text-right">Distance</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($jobs as $job)
                            <tr>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $job->pickup_date?->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ ucfirst($job->job_type) }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $job->truck?->plate_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $job->deliveryPersonnel?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $job->stops->count() }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $job->route_distance_km ? number_format($job->route_distance_km, 1).' km' : '—' }}</td>
                                <td class="px-4 py-3"><x-badge :status="$job->status" dot /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>
