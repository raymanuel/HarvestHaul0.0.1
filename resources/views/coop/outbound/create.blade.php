<x-layout title="Plan a Delivery Trip — Cooperative">
    <x-page-header variant="back-link" title="Plan a Delivery Trip" :back-href="route('coop.outbound.index')" back-label="← Back to Outbound" />

    <form method="GET" action="{{ route('coop.outbound.create') }}" class="mb-6 max-w-xs">
        <x-input name="date" label="Delivery Date" type="date" :value="$date" onchange="this.form.submit()" />
    </form>

    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
        Accepted orders for {{ \Carbon\Carbon::parse($date)->format('M d, Y') }} (or with no delivery-date preference) are grouped into candidate trips by truck capacity. Review each on the map, adjust the checked orders or truck/driver if needed, then create the ones you want.
    </p>

    @if($plan['groups'] === [])
        <x-empty-state type="first-use" title="No accepted orders for this date" description="Accept pending buyer orders first, or pick another date." />
    @endif

    <div class="space-y-6">
        @foreach($plan['groups'] as $i => $group)
            <x-card>
                <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                    <x-section-label :title="$group['label']" width="w-20" />
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                        {{ count($group['orders']) }} stop(s) · {{ $group['load_kg'] }} / {{ $group['capacity_kg'] }} kg ({{ $group['proposed']['utilization'] }}% utilization)
                    </span>
                </div>

                <div id="map-group-{{ $i }}" class="w-full h-64 rounded-xl mb-5 border border-slate-200 dark:border-slate-700"></div>

                <form method="POST" action="{{ route('coop.outbound.store') }}">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">

                    @php
                        $scheduleByOrder = collect($group['proposed']['schedule'])->keyBy('request_id');
                    @endphp

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    <th class="px-4 py-3">Select</th>
                                    <th class="px-4 py-3">Buyer</th>
                                    <th class="px-4 py-3">Quantity</th>
                                    <th class="px-4 py-3">Delivery Location</th>
                                    <th class="px-4 py-3">Est. Arrival</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($group['orders'] as $i2 => $order)
                                    @php
                                        $eta = $scheduleByOrder->get($order->id);
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3">
                                            <input type="checkbox" name="orders[]" value="{{ $order->id }}" checked>
                                            <input type="hidden" name="sequences[{{ $i2 + 1 }}]" value="{{ $order->id }}">
                                        </td>
                                        <td class="px-4 py-3 text-slate-800 dark:text-slate-200">{{ $order->buyer?->name ?? 'Buyer' }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format((float) $order->total_kg, 2) }} kg</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($order->delivery_address, 40) }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                            @if($eta)
                                                +{{ $eta['arrival_min'] }} min
                                            @else
                                                —
                                            @endif
                                        </td>
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
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">These orders don't fit any available truck's remaining capacity today. Free up a truck, add one, or plan them on another date.</p>
            <ul class="text-sm divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($plan['unassigned'] as $order)
                    <li class="py-2 text-slate-700 dark:text-slate-300">{{ $order->buyer?->name ?? 'Buyer' }} — {{ number_format((float) $order->total_kg, 2) }} kg</li>
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
                $order = $group['orders']->firstWhere('id', $stop['request_id'] ?? null);

                return [
                    'lat'   => $stop['lat'],
                    'lng'   => $stop['lng'],
                    'seq'   => $idx + 1,
                    'label' => $order?->buyer?->name ?? 'Buyer',
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
    @endphp

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script>
            function initOutboundMap(elId, depot, stops, geometryCoords) {
                var el = document.getElementById(elId);
                if (!el || !window.L || !depot) return;

                var map = L.map(elId);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                var bounds = [[depot.lat, depot.lng]];
                L.marker([depot.lat, depot.lng], {
                    icon: L.divIcon({ className: 'pickup-marker pickup-marker-depot', html: 'C', iconSize: [22, 22] })
                }).addTo(map).bindPopup('Cooperative');

                stops.forEach(function (s) {
                    L.marker([s.lat, s.lng], {
                        icon: L.divIcon({ className: 'pickup-marker pickup-marker-selected', html: String(s.seq), iconSize: [22, 22] })
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
                    initOutboundMap(m.elId, m.depot, m.stops, m.geometry);
                });
            });
        </script>
    @endpush
</x-layout>
