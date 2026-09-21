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
