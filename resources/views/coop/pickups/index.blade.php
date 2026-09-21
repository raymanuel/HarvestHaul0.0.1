<x-layout title="Pickup Trips — Cooperative">
    <x-page-header title="Pickup Trips" :showDate="true">
        <a href="{{ route('coop.pickups.calendar') }}" class="px-4 py-2 rounded-xl text-xs font-bold border-2 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:border-brand-700/40 transition">Scheduling Calendar</a>
        <a href="{{ route('coop.pickups.create') }}" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-900 text-white dark:bg-white dark:text-slate-900 hover:opacity-90 transition">Plan a Trip</a>
    </x-page-header>

    <x-card>
        <x-section-label title="Trips" />

        @if($trips->isEmpty())
            <x-empty-state type="first-use" title="No pickup trips yet" description="Once you plan a trip from approved requests, it shows up here." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Trip</th>
                            <th class="px-4 py-3">Pickup Date</th>
                            <th class="px-4 py-3">Truck</th>
                            <th class="px-4 py-3">Driver</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($trips as $trip)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <a href="{{ route('coop.pickups.show', $trip) }}" class="font-semibold text-brand-700 dark:text-gold-light hover:underline">Trip #{{ $trip->id }}</a>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $trip->pickup_date?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $trip->truck?->plate_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $trip->deliveryPersonnel?->name ?? '—' }}</td>
                                <td class="px-4 py-3"><x-badge :status="$trip->status" dot /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>
