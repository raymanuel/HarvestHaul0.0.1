@props([
    'pollUrl',
    'stops' => [],
    'depot' => null,
    'height' => '320px',
    'active' => true,
])

@php
    $mapId = 'live-trip-map-'.\Illuminate\Support\Str::random(8);
@endphp

<div>
    <div id="{{ $mapId }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700" style="height: {{ $height }};"></div>
    <p id="{{ $mapId }}-status" class="text-xs text-slate-400 mt-1.5">
        @if($active)
            Waiting for the driver's first position update…
        @else
            This trip is not active — no live position to show.
        @endif
    </p>
</div>

@once
    @push('head')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
        <style>
            .live-truck-marker { display:flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:9999px; background:#2563eb; box-shadow:0 1px 4px rgba(0,0,0,.45); font-size:14px; }
            .live-stop-marker { display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#fff; border-radius:9999px; box-shadow:0 1px 3px rgba(0,0,0,.4); background:#94a3b8; }
        </style>
    @endpush
@endonce

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
        (function () {
            var mapId = '{{ $mapId }}';
            var pollUrl = @json($pollUrl);
            var stops = @json($stops);
            var depot = @json($depot);
            var active = {{ $active ? 'true' : 'false' }};
            var trail = [];
            var truckMarker = null;
            var trailLine = null;

            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById(mapId);
                if (!el || !window.L) return;

                var map = L.map(mapId);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                var bounds = [];
                if (depot && depot.lat && depot.lng) {
                    L.marker([depot.lat, depot.lng], {
                        icon: L.divIcon({ className: 'live-stop-marker', html: 'C', iconSize: [20, 20] }),
                    }).addTo(map).bindPopup('Cooperative');
                    bounds.push([depot.lat, depot.lng]);
                }
                stops.forEach(function (s) {
                    if (!s.lat || !s.lng) return;
                    L.marker([s.lat, s.lng], {
                        icon: L.divIcon({ className: 'live-stop-marker', html: String(s.seq || ''), iconSize: [20, 20] }),
                    }).addTo(map).bindPopup(s.label || 'Stop');
                    bounds.push([s.lat, s.lng]);
                });

                map.setView(bounds.length ? bounds[0] : [7.0, 125.5], bounds.length ? 12 : 7);
                if (bounds.length > 1) map.fitBounds(bounds, { padding: [30, 30] });

                function statusText(text) {
                    var s = document.getElementById(mapId + '-status');
                    if (s) s.textContent = text;
                }

                function poll() {
                    fetch(pollUrl)
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (!data.has_position) {
                                statusText(active ? "Waiting for the driver's first position update…" : 'This trip is not active — no live position to show.');
                                return;
                            }

                            var point = [data.lat, data.lng];
                            trail.push(point);
                            if (trail.length > 20) trail.shift();

                            if (truckMarker) {
                                truckMarker.setLatLng(point);
                            } else {
                                truckMarker = L.marker(point, {
                                    icon: L.divIcon({ className: 'live-truck-marker', html: '🚛', iconSize: [26, 26] }),
                                }).addTo(map);
                            }

                            if (trailLine) {
                                trailLine.setLatLngs(trail);
                            } else {
                                trailLine = L.polyline(trail, { color: '#2563eb', weight: 3 }).addTo(map);
                            }

                            map.panTo(point);

                            var speed = data.speed_kmh ? Math.round(data.speed_kmh) + ' km/h · ' : '';
                            statusText('Last update: ' + speed + data.posted_at_human);
                        })
                        .catch(function () {});
                }

                if (active) {
                    poll();
                    setInterval(poll, 8000);
                }
            });
        })();
    </script>
@endpush
