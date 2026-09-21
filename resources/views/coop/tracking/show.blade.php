@php
    $isDelivery = $haulJob->job_type === 'delivery';
    $depot = ['lat' => (float) ($haulJob->cooperative->latitude ?? 0), 'lng' => (float) ($haulJob->cooperative->longitude ?? 0)];
    $stopPoints = $haulJob->stops->map(function ($stop) {
        $stopIsDelivery = $stop->isDeliveryStop();

        return [
            'lat' => $stopIsDelivery ? (float) ($stop->buyerOrder?->delivery_latitude ?? 0) : (float) ($stop->haulRequest?->pickup_location_lat ?? 0),
            'lng' => $stopIsDelivery ? (float) ($stop->buyerOrder?->delivery_longitude ?? 0) : (float) ($stop->haulRequest?->pickup_location_lng ?? 0),
            'seq' => $stop->sequence_no,
            'label' => $stopIsDelivery ? ($stop->buyerOrder?->buyer?->name ?? 'Buyer') : ($stop->haulRequest?->farmer?->name ?? 'Farmer'),
        ];
    })->filter(fn ($s) => $s['lat'] && $s['lng'])->values();
@endphp

<x-layout :title="'Trip #'.$haulJob->id.' — Location Monitoring'">
    <x-page-header variant="back-link" :title="'Trip #'.$haulJob->id" :back-href="route('coop.tracking.index')" back-label="← Back to Location Monitoring">
        <x-badge :status="$haulJob->status" dot />
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card>
                <x-section-label title="Live Position" width="w-16" />
                <x-live-trip-map
                    :poll-url="route('coop.tracking.location', $haulJob)"
                    :stops="$stopPoints"
                    :depot="$depot"
                    :active="$haulJob->isActiveForTracking()"
                />
            </x-card>
        </div>

        <div>
            <x-card>
                <x-section-label title="Trip Details" width="w-16" />
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Type</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $isDelivery ? 'Delivery' : 'Pickup' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Truck</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $haulJob->truck?->plate_number ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Driver</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $haulJob->deliveryPersonnel?->name ?? '—' }}</dd></div>
                </dl>
            </x-card>
        </div>
    </div>
</x-layout>
