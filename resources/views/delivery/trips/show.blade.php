<x-layout :title="'Trip #'.$haulJob->id.' — Delivery'">
    <x-page-header variant="back-link" :title="'Trip #'.$haulJob->id" :back-href="route('delivery.trips.index')" back-label="← Back to My Trips">
        <x-badge :status="$haulJob->status" dot />
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <x-section-label title="Stops" width="w-16" />

                <div id="map-trip" class="w-full h-72 rounded-xl mb-5 border border-slate-200 dark:border-slate-700"></div>

                @if($haulJob->stops->isEmpty())
                    <x-empty-state type="first-use" title="No stops on this trip" />
                @else
                    <div class="space-y-4">
                        @foreach($haulJob->stops as $stop)
                            <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                                <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                        #{{ $stop->sequence_no }} — {{ $stop->haulRequest?->farmer?->name ?? 'Farmer' }}
                                    </p>
                                    <x-badge :status="$stop->status" />
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $stop->haulRequest?->crop?->name ?? 'Crop' }}
                                    @if($stop->haulRequest?->estimated_weight_kg) · {{ number_format((float) $stop->haulRequest->estimated_weight_kg, 2) }} kg @endif
                                    @if($stop->haulRequest?->pickup_location) · {{ $stop->haulRequest->pickup_location }} @endif
                                </p>
                                @if($stop->planned_arrival_at)
                                    <p class="text-xs text-slate-400 mt-1">Planned arrival: {{ $stop->planned_arrival_at->format('g:i A') }}</p>
                                @endif
                                @if($stop->status === \App\Models\HaulJobStop::STATUS_FAILED && $stop->failure_reason)
                                    <p class="text-xs text-[var(--color-error-text)] mt-1">Reported problem: {{ $stop->failure_reason }}</p>
                                @endif

                                @if(in_array($stop->status, [\App\Models\HaulJobStop::STATUS_PENDING, \App\Models\HaulJobStop::STATUS_ARRIVED], true))
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        @if($stop->status === \App\Models\HaulJobStop::STATUS_PENDING)
                                            <form method="POST" action="{{ route('delivery.trips.stop-status', [$stop, 'arrived']) }}">
                                                @csrf
                                                <button class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-900 text-white dark:bg-white dark:text-slate-900 hover:opacity-90">Arrived</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('delivery.trips.stop-status', [$stop, 'picked_up']) }}">
                                            @csrf
                                            <button class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-500">Picked Up</button>
                                        </form>
                                        <form method="POST" action="{{ route('delivery.trips.stop-status', [$stop, 'skipped']) }}" onsubmit="return confirm('Skip this stop?');">
                                            @csrf
                                            <button class="px-3 py-1.5 rounded-lg text-xs font-bold border-2 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200">Skip</button>
                                        </form>
                                        <x-modal triggerLabel="Report Problem">
                                            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Report a problem at this stop</h2>
                                            <form method="POST" action="{{ route('delivery.trips.stop-status', [$stop, 'failed']) }}" class="space-y-4">
                                                @csrf
                                                <div>
                                                    <label for="reason-{{ $stop->id }}" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">What happened?</label>
                                                    <textarea name="reason" id="reason-{{ $stop->id }}" rows="3" required maxlength="500" class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white" placeholder="e.g. Farmer not reachable, road blocked, wrong location..."></textarea>
                                                </div>
                                                <div class="pt-1 flex justify-end gap-2">
                                                    <button type="button" data-modal-close class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                                                    <button class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-[var(--color-error-text)] hover:opacity-90">Report Problem</button>
                                                </div>
                                            </form>
                                        </x-modal>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($haulJob->stops->whereIn('status', ['pending', 'arrived'])->isEmpty() && $haulJob->status !== \App\Models\HaulJob::STATUS_COMPLETED)
                        <form method="POST" action="{{ route('delivery.trips.complete', $haulJob) }}" class="mt-5">
                            @csrf
                            <x-button variant="primary" full>Complete Trip</x-button>
                        </form>
                    @endif
                @endif
            </x-card>
        </div>

        <div>
            <x-card>
                <x-section-label title="Trip Details" width="w-16" />
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Pickup Date</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $haulJob->pickup_date?->format('M d, Y') ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Truck</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $haulJob->truck?->plate_number ?? '—' }}</dd></div>
                </dl>
            </x-card>
        </div>
    </div>

    @push('head')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
        <style>
            .pickup-marker { display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#fff; border-radius:9999px; box-shadow:0 1px 3px rgba(0,0,0,.4); }
            .pickup-marker-depot { background:#0f172a; }
            .pickup-marker-selected { background:#2563eb; }
            .pickup-marker-completed { background:#16a34a; }
        </style>
    @endpush

    @php
        $tripDepot = ['lat' => (float) ($haulJob->cooperative->latitude ?? 0), 'lng' => (float) ($haulJob->cooperative->longitude ?? 0)];
        $tripStops = $haulJob->stops->map(function ($stop) {
            return [
                'lat'   => (float) ($stop->haulRequest?->pickup_location_lat ?? 0),
                'lng'   => (float) ($stop->haulRequest?->pickup_location_lng ?? 0),
                'seq'   => $stop->sequence_no,
                'label' => $stop->haulRequest?->farmer?->name ?? 'Farmer',
                'state' => $stop->status === \App\Models\HaulJobStop::STATUS_PICKED_UP ? 'completed' : 'selected',
            ];
        })->filter(fn ($s) => $s['lat'] && $s['lng'])->values();
        $tripGeometry = $haulJob->route_geometry;
    @endphp

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('map-trip');
                if (!el || !window.L) return;

                var depot = {!! json_encode($tripDepot) !!};
                var stops = {!! $tripStops->toJson() !!};
                var geometryCoords = {!! $tripGeometry ? json_encode($tripGeometry) : 'null' !!};

                var map = L.map('map-trip');
                var tileUrl = 'https://' + '{s}' + '.tile.openstreetmap.org/' + '{z}' + '/' + '{x}' + '/' + '{y}' + '.png';
                L.tileLayer(tileUrl, {
                    maxZoom: 18,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                var bounds = [[depot.lat, depot.lng]];
                L.marker([depot.lat, depot.lng], {
                    icon: L.divIcon({ className: 'pickup-marker pickup-marker-depot', html: 'C', iconSize: [22, 22] })
                }).addTo(map).bindPopup('Cooperative');

                stops.forEach(function (s) {
                    L.marker([s.lat, s.lng], {
                        icon: L.divIcon({ className: 'pickup-marker pickup-marker-' + s.state, html: String(s.seq), iconSize: [22, 22] })
                    }).addTo(map).bindPopup(s.label);
                    bounds.push([s.lat, s.lng]);
                });

                var isRoad = geometryCoords && geometryCoords.length > 0;
                var line = isRoad
                    ? geometryCoords.map(function (c) { return [c[1], c[0]]; })
                    : (stops.length ? [[depot.lat, depot.lng]].concat(stops.map(function (s) { return [s.lat, s.lng]; })).concat([[depot.lat, depot.lng]]) : null);
                if (line) {
                    L.polyline(line, { color: isRoad ? '#2563eb' : '#94a3b8', weight: 3, dashArray: isRoad ? null : '6,6' }).addTo(map);
                }

                map.fitBounds(bounds, { padding: [30, 30] });
            });
        </script>
    @endpush
</x-layout>
