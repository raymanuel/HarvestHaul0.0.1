<x-layout title="Receiving Dashboard — HarvestHaul">
    <x-page-header title="Receiving" :showDate="true" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-10">
        <x-stat-card badge="Today" title="Pickups Today" :value="$jobsToday->count()" unit="jobs" />
        <x-stat-card badge="Records" title="Awaiting Confirmation" :value="$pendingRecords" unit="records" />
    </div>

    <x-card>
        <x-section-label title="Pickups Scheduled Today" width="w-24" />

        @if($jobsToday->isEmpty())
            <x-empty-state type="cleared" title="No pickups today" description="When the cooperative schedules a pickup for today, it shows here." />
        @else
            <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                @foreach($jobsToday as $job)
                    <li class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                {{ $job->haulRequest?->farmer?->name ?? 'Farmer' }} · {{ $job->haulRequest?->crop?->name ?? 'Crop' }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $job->haulRequest?->estimated_sacks }} sacks · {{ number_format((float) $job->haulRequest?->estimated_weight_kg, 2) }} kg estimated
                            </p>
                        </div>
                        <x-badge :status="$job->status" dot />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-layout>
