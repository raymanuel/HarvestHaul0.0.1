<x-layout title="Receiving Queue — Field">
    <x-page-header title="Receiving Queue" :showDate="true" />

    <x-card>
        <x-section-label title="Today's Trips" />

        @if($jobs->isEmpty())
            <x-empty-state type="cleared" title="Nothing to receive today" description="Trips that have picked up crop today will appear here for receiving." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Trip</th>
                            <th class="px-4 py-3">Truck</th>
                            <th class="px-4 py-3">Stops</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($jobs as $job)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('field.receiving.show', $job) }}" class="font-semibold text-brand-700 dark:text-gold-light hover:underline">Trip #{{ $job->id }}</a>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $job->truck?->plate_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $job->stops->count() }}</td>
                                <td class="px-4 py-3"><x-badge :status="$job->status" dot /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>
