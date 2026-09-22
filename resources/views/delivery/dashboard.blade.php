<x-layout title="Delivery Dashboard — HarvestHaul">
    <x-page-header title="My Runs" :showDate="true" />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card>
            <x-section-label title="Pickups Assigned to Me" width="w-24" />

            @if($haulJobs->isEmpty())
                <x-empty-state type="cleared" title="No pickups right now" description="New pickup jobs assigned to you will appear here." />
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($haulJobs as $job)
                        <li class="py-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                    {{ $job->haulRequest?->farmer?->name ?? 'Farmer' }} · {{ $job->haulRequest?->crop?->name ?? 'Crop' }}
                                </p>
                                <x-badge :status="$job->status" dot />
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $job->pickup_date?->format('M d, Y') ?? 'No date set' }}
                                · {{ $job->truck?->plate_number ?? 'No truck assigned' }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card>
            <x-section-label title="Buyer Deliveries Assigned to Me" width="w-24" />

            @if($deliveries->isEmpty())
                <x-empty-state type="cleared" title="No deliveries right now" description="Buyer deliveries assigned to you will appear here." />
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($deliveries as $delivery)
                        <li class="py-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                    Order {{ $delivery->order?->reference ?? '#'.$delivery->buyer_order_id }}
                                    · {{ $delivery->order?->buyer?->name ?? 'Buyer' }}
                                </p>
                                <x-badge :status="$delivery->status" dot />
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $delivery->delivery_date?->format('M d, Y') ?? 'No date set' }}
                                · {{ $delivery->truck?->plate_number ?? 'No truck assigned' }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-layout>
