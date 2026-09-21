<x-layout :title="'Trip #'.$haulJob->id.' — Cooperative'">
    <x-page-header variant="back-link" :title="'Trip #'.$haulJob->id" :back-href="route('coop.pickups.index')" back-label="← Back to Trips">
        <x-badge :status="$haulJob->status" dot />
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <x-section-label title="Stops" width="w-16" />

                <div id="map-trip" class="w-full h-72 rounded-xl mb-5 border border-slate-200 dark:border-slate-700"></div>

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
        $tripStops = $stops->map(function ($stop) {
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
