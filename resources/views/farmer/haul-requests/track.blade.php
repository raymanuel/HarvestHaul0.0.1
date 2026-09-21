@php
    $depot = $haulJob ? ['lat' => (float) ($haulJob->cooperative->latitude ?? 0), 'lng' => (float) ($haulJob->cooperative->longitude ?? 0)] : null;
    $stopPoints = [[
        'lat' => (float) ($haulRequest->pickup_location_lat ?? 0),
        'lng' => (float) ($haulRequest->pickup_location_lng ?? 0),
        'seq' => null,
        'label' => 'Pickup location',
    ]];
@endphp

<x-layout title="Track Pickup — HarvestHaul">
    <x-page-header variant="back-link" title="Track Pickup" :back-href="route('farmer.haul-requests.index')" back-label="← Back to My Pickup Requests" />

    <x-card>
        @if(! $haulJob)
            <x-empty-state type="first-use" title="Not scheduled yet" description="Your cooperative hasn't scheduled a pickup trip for this request yet. Check back once it's on its way." />
        @else
            <x-section-label title="Live Position" width="w-16" />
            <x-live-trip-map
                :poll-url="route('farmer.haul-requests.track.location', $haulRequest)"
                :stops="$stopPoints"
                :depot="$depot"
                :active="$haulJob->isActiveForTracking()"
            />
        @endif
    </x-card>
</x-layout>
