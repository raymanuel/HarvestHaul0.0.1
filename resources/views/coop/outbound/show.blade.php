<x-layout title="Delivery Trip #{{ $haulJob->id }} — HarvestHaul">
    <x-page-header variant="back-link" title="Delivery Trip #{{ $haulJob->id }}" :back-href="route('coop.outbound.index')" back-label="← Back to Outbound" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <x-section-label title="Stops" width="w-16" />
                    <x-badge :status="$haulJob->status" dot />
                </div>
                <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($haulJob->stops as $stop)
                        <li class="py-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $stop->sequence_no }}. {{ $stop->buyerOrder?->buyer?->name ?? '—' }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $stop->buyerOrder?->reference }} · {{ number_format((float) ($stop->buyerOrder?->total_kg ?? 0), 2) }} kg · {{ $stop->buyerOrder?->delivery_address }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($stop->pod_photo_path)
                                    <a href="{{ route('files.show', ['type' => 'pod-photo', 'id' => $stop->id]) }}" target="_blank" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">View photo</a>
                                @endif
                                <x-badge :status="$stop->status" dot />
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </div>

        <div>
            <x-card>
                <x-section-label title="Trip Details" width="w-16" />
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Date</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $haulJob->pickup_date?->format('M d, Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Truck</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $haulJob->truck?->plate_number ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Driver</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $haulJob->deliveryPersonnel?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Advisory Distance</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $haulJob->route_distance_km ? number_format($haulJob->route_distance_km, 2).' km' : '—' }}</dd></div>
                </dl>
            </x-card>
        </div>
    </div>
</x-layout>
