<x-layout title="Plan a Pickup Trip — Cooperative">
    <x-page-header variant="back-link" title="Plan a Pickup Trip" :back-href="route('coop.pickups.index')" back-label="← Back to Trips" />

    <div class="flex flex-wrap items-end gap-6 mb-6">
        <form method="GET" action="{{ route('coop.pickups.create') }}" class="max-w-xs">
            <x-input name="date" label="Pickup Date" type="date" :value="$date" onchange="this.form.submit()" />
        </form>

        <form method="POST" action="{{ route('coop.settings.update') }}" class="flex items-end gap-2 max-w-xs">
            @csrf
            @method('PUT')
            <x-input
                name="max_cluster_radius_km"
                label="Consolidation Radius (km)"
                type="number"
                step="any"
                :value="old('max_cluster_radius_km', $cooperative->max_cluster_radius_km)"
                placeholder="Default: 20"
            />
            <x-button variant="secondary" size="sm">Save</x-button>
        </form>
    </div>

    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
        Approved requests for {{ \Carbon\Carbon::parse($date)->format('M d, Y') }} are grouped into candidate trips by truck capacity and how close farmers are to each other — within {{ $cooperative->max_cluster_radius_km ?? '20 (default)' }} km. Review each on the map, adjust the checked stops or truck/driver if needed, then create the ones you want. Picking another date re-runs the plan.
    </p>

    @if(in_array($plan['weather']['severity'] ?? 'unknown', ['moderate', 'severe']))
        <div class="mb-6 rounded-xl border px-4 py-3 {{ $plan['weather']['severity'] === 'severe' ? 'bg-[var(--color-error-bg)] border-[var(--color-error-border)] text-[var(--color-error-text)]' : 'bg-[var(--color-warning-bg)] border-[var(--color-warning-border)] text-[var(--color-warning-text)]' }}">
            <p class="text-xs font-bold uppercase tracking-wider mb-1">{{ $plan['weather']['severity'] === 'severe' ? 'Severe weather forecast' : 'Weather advisory' }}</p>
            <p class="text-sm">
                {{ (int) $plan['weather']['forecast']['precipitation_probability'] }}% chance of rain, wind {{ number_format((float) $plan['weather']['forecast']['wind_speed_kmh'], 0) }} km/h forecast for {{ \Carbon\Carbon::parse($date)->format('M d') }}. The estimated times below already include a {{ number_format(($plan['weather']['buffer'] - 1) * 100, 0) }}% buffer for these conditions — review timing carefully before creating trips.
            </p>
        </div>
    @endif

    @if($plan['groups'] === [])
        <x-empty-state type="first-use" title="No approved requests for this date" description="Approve pending pickup requests for this date first, or pick another date." />
    @endif

    <div class="space-y-6">
        @foreach($plan['groups'] as $i => $group)
            <x-card>
                <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                    <x-section-label :title="$group['label']" width="w-20" />
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                        {{ count($group['requests']) }} stop(s) · {{ $group['load_kg'] }} / {{ $group['capacity_kg'] }} kg ({{ $group['proposed']['utilization'] }}% utilization)
                    </span>
                </div>

                <div id="map-group-{{ $i }}" class="w-full h-64 rounded-xl mb-5 border border-slate-200 dark:border-slate-700"></div>

                <form method="POST" action="{{ route('coop.pickups.store') }}">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">

                    @php
                        $scheduleByRequest = collect($group['proposed']['schedule'])->keyBy('request_id');
                    @endphp

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    <th class="px-4 py-3">Select</th>
                                    <th class="px-4 py-3">Farmer</th>
                                    <th class="px-4 py-3">Est. Weight</th>
                                    <th class="px-4 py-3">Window</th>
                                    <th class="px-4 py-3">Est. Arrival</th>
                                    <th class="px-4 py-3">Wait</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($group['requests'] as $i2 => $req)
                                    @php
                                        $eta = $scheduleByRequest->get($req->id);
                                    @endphp
                                    <tr class="{{ $eta && ! $eta['window_ok'] ? 'bg-amber-50 dark:bg-amber-900/10' : '' }}">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" name="requests[]" value="{{ $req->id }}" checked>
                                            <input type="hidden" name="sequences[{{ $i2 + 1 }}]" value="{{ $req->id }}">
                                        </td>
                                        <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $req->farmer?->name ?? 'Farmer' }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format((float) $req->estimated_weight_kg, 2) }} kg</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                            {{ $req->pickup_window_start?->format('g:i A') }} – {{ $req->pickup_window_end?->format('g:i A') }}
                                        </td>
                                        <td class="px-4 py-3 {{ $eta && ! $eta['window_ok'] ? 'text-amber-700 dark:text-amber-400 font-semibold' : 'text-slate-600 dark:text-slate-300' }}">
                                            @if($eta)
                                                +{{ $eta['arrival_min'] }} min{{ $eta['window_ok'] ? '' : ' (outside window)' }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $eta ? $eta['wait_min'].' min' : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5">
                        <x-select name="truck_id" label="Truck" :required="true" :value="$group['truck']?->id" :placeholder="null"
                            :options="$plan['trucks']->mapWithKeys(fn ($t) => [$t->id => $t->truck_name.' ('.$t->plate_number.', '.number_format((float) $t->capacity_kg).' kg)'])->all()" />
                        <x-select name="delivery_personnel_id" label="Delivery Personnel" :required="true" :placeholder="'Select a driver'"
                            :options="$plan['available_drivers']->mapWithKeys(fn ($d) => [$d->id => $d->name])->all()" />
                    </div>

                    <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500 dark:text-slate-400">
                        <span>Routing source: {{ ucfirst($group['matrix']) }}</span>
                        <span>Advisory distance: {{ $group['proposed']['distance_km'] }} km</span>
                        <span>Advisory travel time: {{ $group['proposed']['travel_time'] }}</span>
                        <span>Time windows: {{ $group['proposed']['windows_ok'] ? 'All satisfied' : 'Conflict detected' }}</span>
                    </div>

                    <div class="flex justify-end gap-3 mt-5">
                        <x-button variant="primary" size="sm">Create {{ $group['label'] }}</x-button>
                    </div>
                </form>
            </x-card>
        @endforeach
    </div>

    @if($plan['unassigned']->isNotEmpty())
        <x-card class="mt-6 border-amber-200 dark:border-amber-900/50">
            <x-section-label title="Unassigned — No Truck Has Room" width="w-32" />
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">These requests don't fit any available truck's remaining capacity today. Free up a truck, add one, or plan them on another date.</p>
            <div id="map-unassigned" class="w-full h-56 rounded-xl mb-4 border border-slate-200 dark:border-slate-700"></div>
            <ul class="text-sm divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($plan['unassigned'] as $req)
                    <li class="py-2 text-slate-700 dark:text-slate-300">{{ $req->farmer?->name ?? 'Farmer' }} — {{ number_format((float) $req->estimated_weight_kg, 2) }} kg</li>
                @endforeach
            </ul>
        </x-card>
    @endif

    @push('head')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
        <style>
            .pickup-marker { display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#fff; border-radius:9999px; box-shadow:0 1px 3px rgba(0,0,0,.4); }
            .pickup-marker-depot { background:#0f172a; }
            .pickup-marker-selected { background:#2563eb; }
            .pickup-marker-available { background:#94a3b8; }
        </style>
    @endpush

    @php
        $groupMapData = collect($plan['groups'])->values()->map(function ($group, $i) {
            $depotPoint = collect($group['stops'])->firstWhere('id', 'depot');
            $mapStops = collect($group['proposed']['stop_ids'])->values()->map(function ($stopId, $idx) use ($group) {
                $stop = collect($group['stops'])->firstWhere('id', $stopId);
                if (! $stop) {
                    return null;
                }
                $req = $group['requests']->firstWhere('id', $stop['request_id'] ?? null);

                return [
                    'lat'   => $stop['lat'],
                    'lng'   => $stop['lng'],
                    'seq'   => $idx + 1,
                    'label' => $req?->farmer?->name ?? 'Farmer',
                    'state' => 'selected',
                ];
            })->filter()->values();

            return [
                'elId'     => 'map-group-'.$i,
                'depot'    => $depotPoint,
                'stops'    => $mapStops,
                'geometry' => $group['route_geometry']['geometry'] ?? null,
            ];
        })->values();

        $unassignedMapData = null;
        if ($plan['unassigned']->isNotEmpty()) {
            $unassignedMapData = [
                'elId'     => 'map-unassigned',
                'depot'    => ['lat' => (float) ($cooperative->latitude ?? 0), 'lng' => (float) ($cooperative->longitude ?? 0)],
                'stops'    => $plan['unassigned']->values()->map(fn ($req, $idx) => [
                    'lat'   => (float) $req->pickup_location_lat,
                    'lng'   => (float) $req->pickup_location_lng,
                    'seq'   => $idx + 1,
                    'label' => $req->farmer?->name ?? 'Farmer',
                    'state' => 'available',
                ]),
                'geometry' => null,
            ];
        }
    @endphp

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script>
            function initPickupMap(elId, depot, stops, geometryCoords) {
                var el = document.getElementById(elId);
                if (!el || !window.L || !depot) return;

                var map = L.map(elId);
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
                        icon: L.divIcon({ className: 'pickup-marker pickup-marker-' + (s.state || 'selected'), html: String(s.seq), iconSize: [22, 22] })
                    }).addTo(map).bindPopup(s.label);
                    bounds.push([s.lat, s.lng]);
                });

                var line = null;
                var isRoad = geometryCoords && geometryCoords.length > 0;
                if (isRoad) {
                    line = geometryCoords.map(function (c) { return [c[1], c[0]]; });
                } else if (stops.length) {
                    line = [[depot.lat, depot.lng]].concat(stops.map(function (s) { return [s.lat, s.lng]; })).concat([[depot.lat, depot.lng]]);
                }
                if (line) {
                    L.polyline(line, { color: isRoad ? '#2563eb' : '#94a3b8', weight: 3, dashArray: isRoad ? null : '6,6' }).addTo(map);
                }

                map.fitBounds(bounds, { padding: [30, 30] });
            }

            document.addEventListener('DOMContentLoaded', function () {
                var groupMaps = {!! $groupMapData->toJson() !!};
                groupMaps.forEach(function (m) {
                    initPickupMap(m.elId, m.depot, m.stops, m.geometry);
                });

                var unassignedMap = {!! $unassignedMapData ? json_encode($unassignedMapData) : 'null' !!};
                if (unassignedMap) {
                    initPickupMap(unassignedMap.elId, unassignedMap.depot, unassignedMap.stops, unassignedMap.geometry);
                }
            });
        </script>
    @endpush
</x-layout>
