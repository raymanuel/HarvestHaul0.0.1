<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />

<div id="location-picker-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl shadow-2xl w-full max-w-xl mx-4 overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 pt-5 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-2xl bg-[#16283C]/10 dark:bg-[#16283C]/10 border border-[#16283C]/15 dark:border-[#16283C]/30 flex items-center justify-center text-[#16283C] dark:text-[#D7BC7A] shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white heading-font">Pick a Location</h3>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium" id="location-picker-context-label">Select a point on the map</p>
                </div>
            </div>
            <button onclick="window.__locationPicker.close()" aria-label="Close" class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700/60 transition cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Map --}}
        <div class="px-6 pb-4">
            <div id="location-picker-map" class="w-full h-[220px] rounded-xl border border-[#16283C]/15 shadow-sm overflow-hidden z-0"></div>
        </div>

        {{-- GPS Button --}}
        <div class="px-6 pb-4">
            <button type="button" id="location-picker-gps" class="w-full flex items-center justify-center gap-2 py-2.5 bg-[#16283C]/5 hover:bg-[#16283C]/10 text-[#16283C] dark:text-[#D7BC7A] border border-[#16283C]/20 rounded-xl text-xs font-bold transition cursor-pointer shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Use My GPS Location
            </button>
        </div>

        {{-- Address Display --}}
        <div class="px-6 pb-4">
            <div class="bg-slate-50 dark:bg-slate-700/30 border border-slate-200/50 dark:border-slate-600/40 rounded-xl px-4 py-3">
                <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Address</p>
                <p class="text-xs font-medium text-slate-700 dark:text-slate-300 leading-relaxed" id="location-picker-address">Move the pin to set an address</p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="px-6 pb-6 flex items-center gap-3">
            <button type="button" id="location-picker-save-permanently" class="flex-1 px-4 py-3 bg-[#16283C] hover:bg-[#0E1620] text-white font-bold rounded-xl text-xs shadow-md shadow-[#16283C]/15 transition cursor-pointer inline-flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Save Permanently
            </button>
            <button type="button" id="location-picker-use-context" class="flex-1 px-4 py-3 bg-white hover:bg-slate-50 dark:bg-slate-700/50 dark:hover:bg-slate-700 text-[#16283C] dark:text-[#D7BC7A] border border-[#16283C]/20 dark:border-[#16283C]/30 font-bold rounded-xl text-xs transition cursor-pointer inline-flex items-center justify-center gap-2">
                Use for This Post
            </button>
        </div>

        {{-- Hidden Fields --}}
        <input type="hidden" id="popup_latitude" name="popup_latitude">
        <input type="hidden" id="popup_longitude" name="popup_longitude">
        <input type="hidden" id="popup_address" name="popup_address">
        <input type="hidden" id="popup_save_permanently" name="popup_save_permanently" value="0">
    </div>
</div>

<script>
    (function () {
        const GENSAN = [6.1164, 125.1716];
        let map = null;
        let marker = null;
        let currentCallback = null;

        function initMap() {
            if (map) { map.invalidateSize(); return; }

            map = L.map('location-picker-map', { zoomControl: true }).setView(GENSAN, 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: ' OpenStreetMap contributors' }).addTo(map);

            const greenIcon = L.divIcon({
                html: '<div style="width:18px;height:18px;border-radius:50%;background:#16283C;border:3px solid white;box-shadow:0 3px 8px rgba(45,106,47,0.4);"></div>',
                className: '',
                iconAnchor: [9, 9],
            });

            marker = L.marker(GENSAN, { draggable: true, icon: greenIcon }).addTo(map);

            marker.on('dragend', function (e) { reverseGeocode(e.target.getLatLng()); });
            map.on('click', function (e) { marker.setLatLng(e.latlng); reverseGeocode(e.latlng); });
        }

        function reverseGeocode(latlng) {
            document.getElementById('popup_latitude').value = latlng.lat.toFixed(8);
            document.getElementById('popup_longitude').value = latlng.lng.toFixed(8);

            const addrEl = document.getElementById('location-picker-address');
            addrEl.textContent = 'Looking up address...';

            fetch('https://nominatim.openstreetmap.org/reverse?lat=' + latlng.lat + '&lon=' + latlng.lng + '&format=json')
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data && data.display_name) {
                        var a = data.address;
                        var parts = [a.village || a.suburb || a.neighbourhood || a.hamlet, a.city || a.town || a.municipality, a.province || a.state].filter(Boolean);
                        var short = parts.length ? parts.join(', ') : data.display_name;
                        addrEl.textContent = short;
                        document.getElementById('popup_address').value = short;
                    } else {
                        addrEl.textContent = 'Address not found';
                        document.getElementById('popup_address').value = '';
                    }
                })
                .catch(function () {
                    addrEl.textContent = 'Could not look up address';
                    document.getElementById('popup_address').value = '';
                });
        }

        function fireCallback(savePermanently) {
            document.getElementById('popup_save_permanently').value = savePermanently ? '1' : '0';
            if (typeof currentCallback === 'function') {
                currentCallback({
                    lat: parseFloat(document.getElementById('popup_latitude').value),
                    lng: parseFloat(document.getElementById('popup_longitude').value),
                    address: document.getElementById('popup_address').value,
                    savePermanently: savePermanently,
                });
            }
            window.__locationPicker.close();
        }

        document.getElementById('location-picker-save-permanently').addEventListener('click', function () {
            fireCallback(true);
        });

        document.getElementById('location-picker-use-context').addEventListener('click', function () {
            fireCallback(false);
        });

        document.getElementById('location-picker-gps').addEventListener('click', function () {
            if (!navigator.geolocation) { Swal.fire({ icon: 'error', title: 'Geolocation not supported', text: 'Your browser does not support location services. Pin your location manually.', confirmButtonColor: '#16283C', background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff', color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b', customClass: { popup: 'rounded-xl' } }); return; }
            var btn = this;
            btn.textContent = 'Locating...';
            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    var ll = L.latLng(pos.coords.latitude, pos.coords.longitude);
                    marker.setLatLng(ll);
                    map.setView(ll, 16);
                    reverseGeocode(ll);
                    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg> Use My GPS Location';
                },
                function () {
                    Swal.fire({ icon: 'error', title: 'Could not get location', text: 'Unable to retrieve your location. Pin it manually on the map.', confirmButtonColor: '#16283C', background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff', color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b', customClass: { popup: 'rounded-xl' } });
                    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg> Use My GPS Location';
                }
            );
        });

        window.__locationPicker = {
            open: function (contextLabel, callback) {
                initMap();
                currentCallback = callback || null;

                document.getElementById('location-picker-context-label').textContent = contextLabel
                    ? 'Select a location for this ' + contextLabel.toLowerCase()
                    : 'Select a point on the map';

                document.getElementById('location-picker-use-context').textContent =
                    contextLabel ? 'Use for This ' + contextLabel : 'Use for This';

                document.getElementById('location-picker-address').textContent = 'Move the pin to set an address';
                document.getElementById('popup_latitude').value = '';
                document.getElementById('popup_longitude').value = '';
                document.getElementById('popup_address').value = '';
                document.getElementById('popup_save_permanently').value = '0';

                document.getElementById('location-picker-modal').classList.remove('hidden');

                setTimeout(function () { map.invalidateSize(); }, 150);
            },
            close: function () {
                document.getElementById('location-picker-modal').classList.add('hidden');
            }
        };

        document.getElementById('location-picker-modal').addEventListener('click', function (e) {
            if (e.target === this) window.__locationPicker.close();
        });
    })();
</script>
