@php
    $depot = $haulJob ? ['lat' => (float) ($haulJob->cooperative->latitude ?? 0), 'lng' => (float) ($haulJob->cooperative->longitude ?? 0)] : null;
    $stopPoints = [[
        'lat' => (float) ($buyerOrder->delivery_latitude ?? 0),
        'lng' => (float) ($buyerOrder->delivery_longitude ?? 0),
        'seq' => null,
        'label' => 'Delivery address',
    ]];
@endphp

<x-layout :title="'Track Order '.$buyerOrder->reference">
    <x-page-header variant="back-link" :title="'Track Order '.$buyerOrder->reference" :back-href="route('buyer.orders.show', $buyerOrder)" back-label="← Back to Order" />

    <x-card>
        @if(! $haulJob)
            <x-empty-state type="first-use" title="Not dispatched yet" description="Your cooperative hasn't scheduled a delivery trip for this order yet. Check back once it's out for delivery." />
        @else
            @if($stop?->isFlaggedLate())
                <p class="text-xs font-bold text-[var(--color-error-text)] mb-3">Running late — your cooperative has been notified.</p>
            @elseif($stop?->planned_arrival_at)
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Planned arrival: {{ $stop->planned_arrival_at->format('M d, g:i A') }}</p>
            @else
                <p class="text-xs text-slate-400 dark:text-slate-500 mb-3">Arrival not yet estimated.</p>
            @endif

            <x-section-label title="Live Position" width="w-16" />
            <x-live-trip-map
                :poll-url="route('buyer.orders.track.location', $buyerOrder)"
                :stops="$stopPoints"
                :depot="$depot"
                :active="$haulJob->isActiveForTracking()"
            />
        @endif
    </x-card>
</x-layout>
