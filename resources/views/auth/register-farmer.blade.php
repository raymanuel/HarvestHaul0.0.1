<x-register-layout maxWidth="480px">

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush

    <div class="mb-8 text-center">
        <div class="w-14 h-14 bg-gradient-to-tr from-[#2D6A2F] to-[#5A8A3C] text-white rounded-2xl flex items-center justify-center mx-auto mb-3.5 shadow-md shadow-[#2D6A2F]/10">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.271.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.271.477-4.5 1.253" />
            </svg>
        </div>
        <h2 class="text-xl font-extrabold text-slate-800 heading-font tracking-tight">Farmer Registration</h2>
        <p class="text-xs text-slate-505 mt-1.5 font-semibold">Join the marketplace, pool logistics, and coordinate dispatch</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border border-red-200/50 dark:border-red-900/30 rounded-xl">
            <div class="flex items-start gap-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
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
                    class="px-4 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition">
            </div>
        </div>

        {{-- EMAIL --}}
        <div class="form-group">
            <div class="relative">
                <input type="email" name="email" placeholder="Email Address" required value="{{ old('email') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition">
            </div>
        </div>

        {{-- PHONE --}}
        <div class="form-group">
            <div class="relative">
                <input type="text" name="phone" placeholder="Phone Number" required value="{{ old('phone') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition">
            </div>
        </div>

        {{-- PASSWORD --}}
        <div class="form-group">
            <div class="relative">
                <input type="password" id="password" name="password" placeholder="Password" required
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition">
                <button type="button" onclick="togglePassword('password', 'eye-password')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#2D6A2F] transition focus:outline-none">
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
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition">
                <button type="button" onclick="togglePassword('password_confirmation', 'eye-confirm')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#2D6A2F] transition focus:outline-none">
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
            <label class="text-xs font-bold text-slate-650 block">
                Farm Location <span class="text-red-505">*</span>
            </label>

            <div class="relative">
                <input type="text" id="farm_location_display" name="farm_location" placeholder="Pin your farm on the map below" required readonly value="{{ old('farm_location') }}"
                    class="px-4 py-3 w-full bg-slate-50 border border-[#2D6A2F]/15 rounded-xl focus:outline-none cursor-default text-slate-600 font-medium">
            </div>

            {{-- Hidden coordinate inputs --}}
            <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
            <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">

            {{-- GPS button --}}
            <button type="button" id="use-my-location" class="w-full flex items-center justify-center gap-2 py-2.5 bg-[#EFF2E9] hover:bg-[#EFF2E9]/80 text-[#2D6A2F] border border-[#2D6A2F]/20 rounded-xl text-xs font-bold transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Use My GPS Location
            </button>

            {{-- Map container --}}
            <div id="farm-map" class="w-full h-[200px] rounded-xl border border-[#2D6A2F]/15 shadow-sm overflow-hidden z-0"></div>

            <p class="text-[10px] text-slate-400 font-medium text-center mt-1">
                Drag the pin to your exact farm location.
            </p>
        </div>

        {{-- AFFILIATION --}}
        <div class="form-group space-y-2">
            <label class="text-xs font-bold text-slate-655 block">
                Are you a member of a cooperative? <span class="text-red-505">*</span>
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
                <label id="label-cooperative" class="flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#2D6A2F]/30 hover:bg-[#EFF2E9]/10">
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
            <label class="text-xs font-bold text-slate-650 block">
                Select Your Cooperative <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <div class="relative">
                <select name="cooperative_id" id="cooperative_id"
                    class="pl-4 pr-10 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition appearance-none cursor-pointer text-sm text-slate-700">
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
            <p class="text-[10px] text-slate-400 leading-normal mt-1">
                You can skip this and join a cooperative later from your dashboard profile settings.
            </p>
            @error('cooperative_id')
                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
            @enderror
            @if($cooperatives->isEmpty())
                <p class="text-xs text-slate-500 mt-1 font-medium bg-slate-50 p-2.5 rounded-lg border border-slate-200/50">
                    No verified cooperatives are registered yet. You can sign up as independent and link to a cooperative later.
                </p>
            @endif
        </div>

        {{-- TERMS & CONDITIONS --}}
        <div class="form-group pt-1">
            <label class="flex items-start gap-3 cursor-pointer p-3 rounded-xl bg-[#EFF2E9]/40 border border-[#2D6A2F]/10">
                <input type="checkbox" name="accepted_terms" value="1" {{ old('accepted_terms') ? 'checked' : '' }}
                    class="mt-0.5 w-4 h-4 rounded border-slate-300 text-[#2D6A2F] focus:ring-[#2D6A2F] cursor-pointer shrink-0">
                <span class="text-xs text-slate-500 leading-relaxed">
                    I agree to the
                    <a href="javascript:void(0)" onclick="openLegalModal('{{ route('legal.terms') }}')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#2D6A2F]/10 text-[#2D6A2F] font-semibold hover:bg-[#2D6A2F]/20 hover:text-[#1f4d21] transition-all text-[11px]">Terms & Conditions <span style="font-size:10px;">↗</span></a>
                    and
                    <a href="javascript:void(0)" onclick="openLegalModal('{{ route('legal.privacy') }}')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#2D6A2F]/10 text-[#2D6A2F] font-semibold hover:bg-[#2D6A2F]/20 hover:text-[#1f4d21] transition-all text-[11px]">Privacy Policy <span style="font-size:10px;">↗</span></a>.
                </span>
            </label>
            @error('accepted_terms')
                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-1">
            <button type="submit" class="w-full py-3 bg-gradient-to-r from-[#2D6A2F] to-[#5A8A3C] hover:brightness-105 text-white font-bold rounded-xl text-sm shadow-md shadow-[#2D6A2F]/10 hover:shadow-lg transition duration-200 transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
                Register as Farmer
            </button>
        </div>

        <div class="mt-6 pt-5 border-t border-slate-100/80 text-center text-xs font-semibold text-slate-450">
            Not a farmer?
            <a href="{{ route('register.role', 'logistics_partner') }}"
                class="text-[#2D6A2F] hover:text-[#2D6A2F]/80 transition ml-1 hover:underline">
                Sign up as Logistics Coordinator
            </a>
        </div>
        <div class="mt-3 text-center">
            <a href="/" class="text-slate-400 hover:text-[#2D6A2F] text-xs font-bold flex items-center justify-center gap-1">
                ← Return to Homepage
            </a>
        </div>
        {{-- LEGAL MODAL --}}
        <div id="legal-modal-overlay" style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);display:none;align-items:center;justify-content:center;padding:1rem;backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);" onclick="if(event.target===this)closeLegalModal()">
            <div onclick="event.stopPropagation()" style="background:#fff;border-radius:1.5rem;max-width:640px;width:100%;max-height:80vh;overflow-y:auto;position:relative;box-shadow:0 25px 50px -12px rgba(0,0,0,0.3);scrollbar-width:thin;scrollbar-color:#d1d5db transparent;">
                <div style="position:sticky;top:0;z-index:10;background:linear-gradient(135deg,#2D6A2F,#5A8A3C);border-radius:1.5rem 1.5rem 0 0;padding:1.25rem 2rem 1rem;margin:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <span style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.7);">HarvestHaul</span>
                            <div id="legal-modal-title" style="font-size:1.1rem;font-weight:700;color:#fff;font-family:'Outfit',sans-serif;margin-top:0.15rem;">Loading...</div>
                        </div>
                        <button onclick="closeLegalModal()" style="width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:none;background:rgba(255,255,255,0.2);color:#fff;cursor:pointer;font-size:1.1rem;transition:all 0.15s;font-family:inherit;backdrop-filter:blur(4px);" onmouseover="this.style.background='rgba(255,255,255,0.35)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">&times;</button>
                    </div>
                </div>
                <div style="padding:1.5rem 2rem 2rem;">
                    <div id="legal-modal-body" style="font-family:'Figtree',sans-serif;font-size:0.9rem;line-height:1.7;color:#374151;text-align:justify;"></div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function openLegalModal(url) {
            const overlay = document.getElementById('legal-modal-overlay');
            const body = document.getElementById('legal-modal-body');
            const title = document.getElementById('legal-modal-title');
            body.innerHTML = '<div style="text-align:center;padding:3rem 1rem;"><div style="width:32px;height:32px;border:3px solid #e5e7eb;border-top-color:#2D6A2F;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 1rem;"></div><p style="color:#9ca3af;font-size:0.85rem;">Loading...</p></div>';
            title.textContent = 'HarvestHaul';
            overlay.style.display = 'flex';
            fetch(url).then(r => r.text()).then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                body.innerHTML = doc.querySelector('.container').innerHTML;
                const h1 = body.querySelector('h1');
                if (h1) title.textContent = h1.textContent;
            }).catch(() => {
                body.innerHTML = '<p style="color:#dc2626;padding:2rem;text-align:center;">Failed to load. Please try again.</p>';
            });
        }
        function closeLegalModal() {
            document.getElementById('legal-modal-overlay').style.display = 'none';
        }
        document.addEventListener('click', function(e) {
            if (e.target.id === 'legal-modal-overlay') closeLegalModal();
        });
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // PASSWORD TOGGLE
        function togglePassword(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon  = document.getElementById(iconId);
            const isHidden = field.type === 'password';

            field.type = isHidden ? 'text' : 'password';

            icon.innerHTML = isHidden
                ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                   <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                   <line x1="1" y1="1" x2="23" y2="23"/>`
                : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                   <circle cx="12" cy="12" r="3"/>`;
        }

        // LEAFLET MAP — General Santos City default center
        const GENSAN = [6.1164, 125.1716];

        const map = L.map('farm-map', { zoomControl: true }).setView(GENSAN, 13);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '© OpenStreetMap contributors © CARTO',
            subdomains: 'abcd',
            maxZoom: 19,
        }).addTo(map);

        // Custom green marker matching #2D6A2F
        const greenIcon = L.divIcon({
            html: `<div style="
                width: 18px; height: 18px; border-radius: 50%;
                background: #2D6A2F; border: 3px solid white;
                box-shadow: 0 3px 8px rgba(45, 106, 47, 0.4);
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

        // REVERSE GEOCODE via Nominatim
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

        // UPDATE HIDDEN INPUTS ON MARKER MOVE
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

        // GPS BUTTON
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
                    btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Use My GPS Location`;
                },
                function () {
                    alert('Unable to retrieve your location. Please pin manually.');
                    btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Use My GPS Location`;
                }
            );
        });

        // AFFILIATION TOGGLE
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
                labelCoop.className = "flex flex-col items-center justify-center p-4 border-2 border-[#2D6A2F] bg-[#EFF2E9]/30 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 scale-[1.02] shadow-sm";
            } else {
                labelCoop.className = "flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#2D6A2F]/30 hover:bg-[#EFF2E9]/10";
            }

            coopField.style.display = cooperative.checked ? 'block' : 'none';
        }

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
