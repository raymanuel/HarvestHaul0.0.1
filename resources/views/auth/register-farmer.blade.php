<x-register-layout maxWidth="480px">

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush

    <div class="mb-8 text-center">
        <div class="w-14 h-14 bg-gradient-to-tr from-emerald-600 to-teal-500 text-white rounded-2xl flex items-center justify-center mx-auto mb-3.5 shadow-md shadow-emerald-500/10">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.271.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.271.477-4.5 1.253" />
            </svg>
        </div>
        <h2 class="text-xl font-extrabold text-slate-800 heading-font tracking-tight">Farmer Registration</h2>
        <p class="text-xs text-slate-500 mt-1.5 font-semibold">Join the marketplace, pool logistics, and coordinate dispatch</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border border-red-200/50 dark:border-red-900/30 rounded-xl">
            <div class="flex items-start gap-2.5">
                <span class="text-red-500 mt-0.5 text-xs">⚠️</span>
                <ul class="text-xs text-red-600 dark:text-red-400 list-disc list-inside space-y-1 text-left">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('register.store') }}" method="POST" class="space-y-4">
        @csrf
        <input type="hidden" name="role" value="farmer">

        {{-- NAME --}}
        <div class="form-group">
            <div class="relative">
                <input type="text" name="name" placeholder="Full Name" required value="{{ old('name') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition">
            </div>
        </div>

        {{-- EMAIL --}}
        <div class="form-group">
            <div class="relative">
                <input type="email" name="email" placeholder="Email Address" required value="{{ old('email') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition">
            </div>
        </div>

        {{-- PHONE --}}
        <div class="form-group">
            <div class="relative">
                <input type="text" name="phone" placeholder="Phone Number" required value="{{ old('phone') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition">
            </div>
        </div>

        {{-- PASSWORD --}}
        <div class="form-group">
            <div class="relative">
                <input type="password" id="password" name="password" placeholder="Password" required
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition">
                <button type="button" onclick="togglePassword('password', 'eye-password')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition focus:outline-none">
                    <svg id="eye-password" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- CONFIRM PASSWORD --}}
        <div class="form-group">
            <div class="relative">
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm Password" required
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition">
                <button type="button" onclick="togglePassword('password_confirmation', 'eye-confirm')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition focus:outline-none">
                    <svg id="eye-confirm" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- FARM LOCATION — MAP PIN --}}
        <div class="form-group space-y-2">
            <label class="text-xs font-bold text-slate-600 block">
                Farm Location <span class="text-red-500">*</span>
            </label>

            <div class="relative">
                <input type="text" id="farm_location_display" name="farm_location" placeholder="Pin your farm on the map below" required value="{{ old('farm_location') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition">
            </div>

            {{-- Hidden coordinate inputs --}}
            <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
            <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">

            {{-- GPS button --}}
            <button type="button" id="use-my-location" class="w-full flex items-center justify-center gap-2 py-2.5 bg-emerald-50 hover:bg-emerald-100/80 text-emerald-700 border border-emerald-200/50 rounded-xl text-xs font-bold transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                📍 Use My GPS Location
            </button>

            {{-- Map container --}}
            <div id="farm-map" class="w-full h-[200px] rounded-xl border border-slate-200/80 shadow-sm overflow-hidden z-0"></div>

            <p class="text-[10px] text-slate-400 font-medium text-center mt-1">
                Drag the pin to your exact farm location.
            </p>
        </div>

        {{-- AFFILIATION --}}
        <div class="form-group space-y-2">
            <label class="text-xs font-bold text-slate-600 block">
                Are you a member of a cooperative? <span class="text-red-500">*</span>
            </label>

            <div class="grid grid-cols-2 gap-3.5">
                <!-- Independent Card -->
                <label id="label-independent" class="flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-amber-300 hover:bg-amber-50/10">
                    <input type="radio" name="affiliation_type" value="independent"
                        {{ old('affiliation_type') === 'independent' ? 'checked' : '' }}
                        class="hidden" onchange="handleAffiliation()">
                    <span class="text-xs font-bold text-slate-800">Independent</span>
                    <span class="text-[9px] text-slate-400 font-medium leading-tight">No cooperative</span>
                </label>

                <!-- Cooperative Card -->
                <label id="label-cooperative" class="flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-emerald-300 hover:bg-emerald-50/10">
                    <input type="radio" name="affiliation_type" value="cooperative"
                        {{ old('affiliation_type') === 'cooperative' ? 'checked' : '' }}
                        class="hidden" onchange="handleAffiliation()">
                    <span class="text-xs font-bold text-slate-800">Cooperative</span>
                    <span class="text-[9px] text-slate-400 font-medium leading-tight">Under a cooperative</span>
                </label>
            </div>
        </div>

        {{-- Cooperative dropdown — only shown if under a coop --}}
        <div id="coop-field" style="display:none;" class="form-group space-y-1.5">
            <label class="text-xs font-bold text-slate-600 block">
                Select Your Cooperative <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <select name="cooperative_id" id="cooperative_id"
                    class="pl-4 pr-10 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition appearance-none cursor-pointer text-sm text-slate-700">
                    <option value="">— Select your cooperative —</option>
                    @foreach($cooperatives as $coop)
                        <option value="{{ $coop->id }}"
                            {{ old('cooperative_id') == $coop->id ? 'selected' : '' }}>
                            {{ $coop->company_name }}
                        </option>
                    @endforeach
                </select>
                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </span>
            </div>
            @error('cooperative_id')
                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
            @enderror
            @if($cooperatives->isEmpty())
                <p class="text-xs text-red-500 mt-1 font-semibold">
                    No verified cooperatives are registered yet. Please sign up as independent for now.
                </p>
            @endif
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 text-white font-bold rounded-xl text-sm shadow-md shadow-emerald-500/10 hover:shadow-lg transition duration-200 transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
                Register as Farmer
            </button>
        </div>

        <div class="mt-6 pt-5 border-t border-slate-100/80 text-center text-xs font-semibold text-slate-400">
            Not a farmer?
            <a href="{{ route('register.role', 'logistics_partner') }}"
                class="text-emerald-600 hover:text-emerald-700 transition ml-1 hover:underline">
                Sign up as Logistics Coordinator
            </a>
        </div>
    </form>

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // -------------------------------------------------------
        // PASSWORD TOGGLE
        // -------------------------------------------------------
        function togglePassword(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon  = document.getElementById(iconId);
            const isHidden = field.type === 'password';

            field.type = isHidden ? 'text' : 'password';

            // Swap icon: open eye vs slashed eye
            icon.innerHTML = isHidden
                ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                   <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                   <line x1="1" y1="1" x2="23" y2="23"/>`
                : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                   <circle cx="12" cy="12" r="3"/>`;
        }

        // -------------------------------------------------------
        // LEAFLET MAP — General Santos City default center
        // -------------------------------------------------------
        const GENSAN = [6.1164, 125.1716];

        const map = L.map('farm-map', { zoomControl: true }).setView(GENSAN, 13);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '© OpenStreetMap contributors © CARTO',
            subdomains: 'abcd',
            maxZoom: 19,
        }).addTo(map);

        // Custom green marker matching emerald-600
        const greenIcon = L.divIcon({
            html: `<div style="
                width: 18px; height: 18px; border-radius: 50%;
                background: #059669; border: 3px solid white;
                box-shadow: 0 3px 8px rgba(5, 150, 105, 0.4);
            "></div>`,
            className: '',
            iconAnchor: [9, 9],
        });

        // Restore old pin position if validation failed and page reloaded
        const oldLat = {{ old('latitude', 'null') }};
        const oldLng = {{ old('longitude', 'null') }};
        const initPos = (oldLat && oldLng) ? [oldLat, oldLng] : GENSAN;

        const marker = L.marker(initPos, {
            draggable: true,
            icon: greenIcon,
        }).addTo(map);

        if (oldLat && oldLng) {
            map.setView(initPos, 15);
        }

        // -------------------------------------------------------
        // REVERSE GEOCODE via Nominatim
        // -------------------------------------------------------
        function reverseGeocode(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.display_name) {
                        const addr = data.address;
                        const parts = [
                            addr.village || addr.suburb || addr.neighbourhood || addr.hamlet,
                            addr.city || addr.town || addr.municipality,
                            addr.province || addr.state,
                        ].filter(Boolean);

                        document.getElementById('farm_location_display').value =
                            parts.length ? parts.join(', ') : data.display_name;
                    }
                })
                .catch(() => {});
        }

        // -------------------------------------------------------
        // UPDATE HIDDEN INPUTS ON MARKER MOVE
        // -------------------------------------------------------
        function updateCoords(latlng) {
            document.getElementById('latitude').value  = latlng.lat.toFixed(8);
            document.getElementById('longitude').value = latlng.lng.toFixed(8);
            reverseGeocode(latlng.lat, latlng.lng);
        }

        marker.on('dragend', function (e) {
            updateCoords(e.target.getLatLng());
        });

        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            updateCoords(e.latlng);
        });

        // -------------------------------------------------------
        // GPS BUTTON
        // -------------------------------------------------------
        document.getElementById('use-my-location').addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                return;
            }

            this.textContent = 'Locating...';
            const btn = this;

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    const latlng = L.latLng(pos.coords.latitude, pos.coords.longitude);
                    marker.setLatLng(latlng);
                    map.setView(latlng, 16);
                    updateCoords(latlng);
                    btn.innerHTML = `📍 Use My GPS Location`;
                },
                function () {
                    alert('Unable to retrieve your location. Please pin manually.');
                    btn.innerHTML = `📍 Use My GPS Location`;
                }
            );
        });

        // ── AFFILIATION TOGGLE ──────────────────────────────────
        function handleAffiliation() {
            const independent = document.querySelector('input[name="affiliation_type"][value="independent"]');
            const cooperative = document.querySelector('input[name="affiliation_type"][value="cooperative"]');
            const coopField   = document.getElementById('coop-field');
            const labelInd    = document.getElementById('label-independent');
            const labelCoop   = document.getElementById('label-cooperative');

            if (independent.checked) {
                labelInd.className = "flex flex-col items-center justify-center p-4 border-2 border-amber-500 bg-amber-50/20 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 scale-[1.02] shadow-sm";
            } else {
                labelInd.className = "flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-amber-300 hover:bg-amber-50/10";
            }

            if (cooperative.checked) {
                labelCoop.className = "flex flex-col items-center justify-center p-4 border-2 border-emerald-500 bg-emerald-50/20 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 scale-[1.02] shadow-sm";
            } else {
                labelCoop.className = "flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-emerald-300 hover:bg-emerald-50/10";
            }

            coopField.style.display = cooperative.checked ? 'block' : 'none';
            const coopSelect = document.getElementById('cooperative_id');
            coopSelect.required = cooperative.checked;
        }

        // Restore affiliation state on validation failure
        document.addEventListener('DOMContentLoaded', function () {
            const oldAffiliation = "{{ old('affiliation_type') }}";
            if (oldAffiliation) {
                const radio = document.querySelector(`input[name="affiliation_type"][value="${oldAffiliation}"]`);
                if (radio) { radio.checked = true; handleAffiliation(); }
            }

            @if($errors->has('cooperative_id'))
                document.getElementById('coop-field').style.display = 'block';
            @endif
        });
    </script>
    @endpush

</x-register-layout>
