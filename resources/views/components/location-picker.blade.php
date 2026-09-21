@props([
    'latField' => 'latitude',
    'lngField' => 'longitude',
    'label' => 'Pin Location on Map',
    'centerLat' => 7.0,
    'centerLng' => 125.5,
    'zoom' => 8,
    'height' => '280px',
])

@php
    $elId = 'loc-picker-'.\Illuminate\Support\Str::random(8);
    $oldLat = old($latField);
    $oldLng = old($lngField);
@endphp

<div>
    <div class="flex items-center justify-between mb-1.5">
        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ $label }} <span class="text-[var(--color-error-text)]">*</span></label>
        <button type="button" onclick="locPickerUseMyLocation('{{ $elId }}')" class="text-[11px] font-bold text-brand-700 dark:text-gold-light hover:underline">Use my location</button>
    </div>
    <div id="{{ $elId }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-600" style="height: {{ $height }};"></div>
    <p id="{{ $elId }}-hint" class="text-xs text-slate-400 mt-1">Click or drag the pin to set the exact location.</p>
    @error($latField)
        <p class="text-xs text-[var(--color-error-text)] mt-1">{{ $message }}</p>
    @enderror

    <input type="hidden" name="{{ $latField }}" id="{{ $elId }}-lat" value="{{ $oldLat }}">
    <input type="hidden" name="{{ $lngField }}" id="{{ $elId }}-lng" value="{{ $oldLng }}">
</div>

@once
    @push('head')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    @endpush
    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script>
            window.__locPickers = window.__locPickers || {};

            function locPickerUseMyLocation(elId) {
                if (!navigator.geolocation) return;
                navigator.geolocation.getCurrentPosition(function (pos) {
                    locPickerSetPoint(elId, pos.coords.latitude, pos.coords.longitude);
                    var entry = window.__locPickers[elId];
                    if (entry) entry.map.setView([pos.coords.latitude, pos.coords.longitude], 15);
                });
            }

            function locPickerSetPoint(elId, lat, lng) {
                var entry = window.__locPickers[elId];
                if (!entry) return;
                document.getElementById(elId + '-lat').value = lat.toFixed(7);
                document.getElementById(elId + '-lng').value = lng.toFixed(7);
                if (entry.marker) {
                    entry.marker.setLatLng([lat, lng]);
                } else {
                    entry.marker = L.marker([lat, lng], { draggable: true }).addTo(entry.map);
                    entry.marker.on('dragend', function () {
                        var ll = entry.marker.getLatLng();
                        locPickerSetPoint(elId, ll.lat, ll.lng);
                    });
                }
            }
        </script>
    @endpush
@endonce

@push('scripts')
    <script>
        (function () {
            var elId = '{{ $elId }}';
            var initLat = {{ $oldLat ?: $centerLat }};
            var initLng = {{ $oldLng ?: $centerLng }};
            var hasPoint = {{ $oldLat && $oldLng ? 'true' : 'false' }};

            document.addEventListener('DOMContentLoaded', function () {
                var map = L.map(elId).setView([initLat, initLng], hasPoint ? 15 : {{ $zoom }});
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                window.__locPickers[elId] = { map: map, marker: null };

                if (hasPoint) {
                    locPickerSetPoint(elId, initLat, initLng);
                }

                map.on('click', function (e) {
                    locPickerSetPoint(elId, e.latlng.lat, e.latlng.lng);
                });
            });
        })();
    </script>
@endpush
