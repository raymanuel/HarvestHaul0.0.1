<x-layout :title="'Trip #'.$haulJob->id.' — Cooperative'">
    <x-page-header variant="back-link" :title="'Trip #'.$haulJob->id" :back-href="route('coop.pickups.index')" back-label="← Back to Trips">
        <x-badge :status="$haulJob->status" dot />
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <x-section-label title="Stops" width="w-16" />
                @if($stops->isEmpty())
                    <x-empty-state type="first-use" title="No stops on this trip" />
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">Farmer</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($stops as $stop)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $stop->sequence_no }}</td>
                                        <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $stop->haulRequest?->farmer?->name ?? 'Farmer' }}</td>
                                        <td class="px-4 py-3"><x-badge :status="$stop->status" /></td>
                                        <td class="px-4 py-3 text-right">
                                            @if(! in_array($haulJob->status, [\App\Models\HaulJob::STATUS_COMPLETED, \App\Models\HaulJob::STATUS_CANCELLED], true))
                                                <form method="POST" action="{{ route('coop.pickups.stops.remove', $stop) }}" onsubmit="return confirm('Remove this stop? The pickup request goes back to the approved queue.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-xs font-bold text-[var(--color-error-text)] hover:underline">Remove</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>

            @if($haulJob->status === \App\Models\HaulJob::STATUS_SCHEDULED)
                <x-card>
                    <x-section-label title="Reassign Truck / Personnel" width="w-24" />
                    <form method="POST" action="{{ route('coop.pickups.reassign', $haulJob) }}">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-select name="truck_id" label="Truck" :required="true" :value="$haulJob->truck_id" :placeholder="null"
                                :options="$trucks->mapWithKeys(fn ($t) => [$t->id => $t->truck_name.' ('.$t->plate_number.')'])->all()" />
                            <x-select name="delivery_personnel_id" label="Delivery Personnel" :required="true" :value="$haulJob->delivery_personnel_id" :placeholder="null"
                                :options="$drivers->mapWithKeys(fn ($d) => [$d->id => $d->name])->all()" />
                        </div>
                        <div class="flex justify-end mt-4">
                            <x-button variant="primary" size="sm">Reassign</x-button>
                        </div>
                    </form>
                </x-card>

                <x-card>
                    <x-section-label title="Reschedule Trip" width="w-20" />
                    <form method="POST" action="{{ route('coop.pickups.reschedule', $haulJob) }}" class="flex items-end gap-4">
                        @csrf
                        @method('PUT')
                        <x-input name="date" label="New Pickup Date" type="date" :value="$haulJob->pickup_date?->toDateString()" required />
                        <x-button variant="primary" size="sm">Reschedule</x-button>
                    </form>
                </x-card>
            @endif
        </div>

        <div>
            <x-card>
                <x-section-label title="Trip Details" width="w-16" />
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Pickup Date</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $haulJob->pickup_date?->format('M d, Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Truck</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $haulJob->truck?->plate_number ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Driver</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $haulJob->deliveryPersonnel?->name ?? '—' }}</dd></div>
                </dl>
            </x-card>
        </div>
    </div>
</x-layout>
