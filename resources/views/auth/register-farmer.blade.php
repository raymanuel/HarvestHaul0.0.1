<x-register-layout maxWidth="480px">

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-500/20 border border-red-500/50 rounded-lg">
            <ul class="text-xs text-red-400 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('register.store') }}" method="POST">
        @csrf
        <input type="hidden" name="role" value="farmer">

        {{-- NAME --}}
        <div class="form-group">
            <input type="text" name="name" placeholder="Full Name" required value="{{ old('name') }}">
        </div>

        {{-- EMAIL --}}
        <div class="form-group">
            <input type="email" name="email" placeholder="Email Address" required value="{{ old('email') }}">
        </div>

        {{-- PHONE --}}
        <div class="form-group">
            <input type="text" name="phone" placeholder="Phone Number" required value="{{ old('phone') }}">
        </div>

        {{-- PASSWORD --}}
        <div class="form-group">
            <div style="position: relative;">
                <input type="password" id="password" name="password" placeholder="Password" required
                    style="padding-right: 3rem;">
                <button type="button" onclick="togglePassword('password', 'eye-password')"
                    style="position:absolute; right:0.85rem; top:50%; transform:translateY(-50%);
                           background:none; border:none; cursor:pointer; padding:0; color:#6b7280; width:auto;">
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
            <div style="position: relative;">
                <input type="password" id="password_confirmation" name="password_confirmation"
                    placeholder="Confirm Password" required style="padding-right: 3rem;">
                <button type="button" onclick="togglePassword('password_confirmation', 'eye-confirm')"
                    style="position:absolute; right:0.85rem; top:50%; transform:translateY(-50%);
                           background:none; border:none; cursor:pointer; padding:0; color:#6b7280; width:auto;">
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
        <div class="form-group">
            <label style="font-size: 0.8rem; font-weight: 600; color: #374151; display:block; margin-bottom: 0.4rem;">
                Farm Location
            </label>

            {{-- Auto-populated from reverse geocode — editable fallback --}}
            <input
                type="text"
                id="farm_location_display"
                name="farm_location"
                placeholder="Pin your farm on the map below"
                required
                value="{{ old('farm_location') }}"
                style="margin-bottom: 0.6rem;"
            >

            {{-- Hidden coordinate inputs --}}
            <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
            <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">

            {{-- GPS button --}}
            <button type="button" id="use-my-location"
                style="width:100%; padding: 0.6rem; margin-bottom: 0.6rem;
                       background: rgba(45,138,55,0.08); color: #2D8A37;
                       border: 1px solid rgba(45,138,55,0.3); border-radius: 0.6rem;
                       font-size: 0.85rem; font-weight: 600; cursor: pointer;
                       transition: background 0.2s;">
                📍 Use My GPS Location
            </button>

            {{-- Map container --}}
            <div id="farm-map" style="width:100%; height:220px; border-radius:0.75rem;
                 border: 1px solid rgba(0,0,0,0.08); overflow:hidden; z-index:0;"></div>

            <p style="font-size: 0.72rem; color: #9ca3af; margin-top: 0.4rem; text-align:center;">
                Drag the pin to your exact farm location.
            </p>
        </div>

        {{-- AFFILIATION --}}
        <div class="form-group">
            <label style="font-size:0.8rem; font-weight:600; color:#374151; display:block; margin-bottom:0.5rem;">
                Are you a member of a cooperative? <span style="color:red">*</span>
            </label>
            @error('affiliation_type')
                <p style="font-size:0.75rem; color:#ef4444; margin-bottom:0.5rem;">{{ $message }}</p>
            @enderror
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                <label id="label-independent"
                    style="display:flex; flex-direction:column; align-items:center; justify-content:center;
                           padding:1rem 0.75rem; border:2px solid #e5e7eb; border-radius:0.75rem;
                           cursor:pointer; transition:all 0.2s; text-align:center; gap:0.35rem;">
                    <input type="radio" name="affiliation_type" value="independent"
                        {{ old('affiliation_type') === 'independent' ? 'checked' : '' }}
                        style="display:none;" onchange="handleAffiliation()">
                    <span style="font-size:1.75rem;">🧑‍🌾</span>
                    <span style="font-size:0.875rem; font-weight:700; color:#92400e;">Independent</span>
                    <span style="font-size:0.7rem; color:#6b7280;">No cooperative</span>
                </label>

                <label id="label-cooperative"
                    style="display:flex; flex-direction:column; align-items:center; justify-content:center;
                           padding:1rem 0.75rem; border:2px solid #e5e7eb; border-radius:0.75rem;
                           cursor:pointer; transition:all 0.2s; text-align:center; gap:0.35rem;">
                    <input type="radio" name="affiliation_type" value="cooperative"
                        {{ old('affiliation_type') === 'cooperative' ? 'checked' : '' }}
                        style="display:none;" onchange="handleAffiliation()">
                    <span style="font-size:1.75rem;">🤝</span>
                    <span style="font-size:0.875rem; font-weight:700; color:#166534;">Under a Cooperative</span>
                    <span style="font-size:0.7rem; color:#6b7280;">I have a coop</span>
                </label>
            </div>
        </div>

        {{-- Cooperative dropdown — only shown if under a coop --}}
        <div id="coop-field" style="display:none;" class="form-group">
            <label style="font-size:0.8rem; font-weight:600; color:#374151; display:block; margin-bottom:0.4rem;">
                Select Your Cooperative <span style="color:red">*</span>
            </label>
            <select name="cooperative_id" id="cooperative_id"
                style="width:100%; padding:0.75rem 1rem; border:1px solid #e5e7eb;
                       border-radius:0.75rem; font-size:0.95rem; color:#111827;
                       background:white; outline:none; cursor:pointer;
                       {{ $errors->has('cooperative_id') ? 'border-color:#ef4444;' : '' }}">
                <option value="">— Select your cooperative —</option>
                @foreach($cooperatives as $coop)
                    <option value="{{ $coop->id }}"
                        {{ old('cooperative_id') == $coop->id ? 'selected' : '' }}>
                        {{ $coop->company_name }}
                    </option>
                @endforeach
            </select>
            @error('cooperative_id')
                <p style="font-size:0.75rem; color:#ef4444; margin-top:0.4rem;">{{ $message }}</p>
            @enderror
            @if($cooperatives->isEmpty())
                <p style="font-size:0.75rem; color:#ef4444; margin-top:0.4rem;">
                    No verified cooperatives are registered yet. Please sign up as independent for now.
                </p>
            @endif
        </div>

        <button type="submit" class="primary-btn">Register as Farmer</button>

        <div style="margin-top: 1.5rem; font-size: 0.85rem; color: #6b7280;">
            Not a farmer?
            <a href="{{ route('register.role', 'logistics_partner') }}"
                style="color: #2D8A37; font-weight: 600; text-decoration: none;">
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

        // Custom green marker
        const greenIcon = L.divIcon({
            html: `<div style="
                width: 16px; height: 16px; border-radius: 50%;
                background: #2D8A37; border: 3px solid white;
                box-shadow: 0 2px 6px rgba(0,0,0,0.4);
            "></div>`,
            className: '',
            iconAnchor: [8, 8],
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
                        // Extract readable parts — prioritize suburb/city level
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
                .catch(() => {
                    // Geocode failed — user can type manually, don't block
                });
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

        // Click on map moves the marker
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
                    btn.textContent = '📍 Use My GPS Location';
                },
                function () {
                    alert('Unable to retrieve your location. Please pin manually.');
                    btn.textContent = '📍 Use My GPS Location';
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

            labelInd.style.borderColor  = independent.checked ? '#d97706' : '#e5e7eb';
            labelInd.style.background   = independent.checked ? '#fffbeb' : '';
            labelCoop.style.borderColor = cooperative.checked  ? '#2D8A37' : '#e5e7eb';
            labelCoop.style.background  = cooperative.checked  ? '#f0fdf4' : '';

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
