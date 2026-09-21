<x-layout title="Location Monitoring — HarvestHaul">
    <x-page-header title="Location Monitoring" :showDate="true" />

    @if($jobs->isEmpty())
        <x-card>
            <x-empty-state type="first-use" title="No active trips" description="Pickup and delivery trips currently in progress will show up here with their live position." />
        </x-card>
    @else
        <x-card>
            <x-section-label title="Active Trips" width="w-16" />
            <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                @foreach($jobs as $job)
                    <li class="py-3 flex items-center justify-between gap-3">
                        <a href="{{ route('coop.tracking.show', $job) }}" class="hover:underline min-w-0">
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                Trip #{{ $job->id }} · {{ $job->job_type === 'delivery' ? 'Delivery' : 'Pickup' }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $job->truck?->plate_number ?? 'No truck' }} · {{ $job->stops->count() }} stop(s)
                            </p>
                        </a>
                        <x-badge :status="$job->status" dot />
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</x-layout>
