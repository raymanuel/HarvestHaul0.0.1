<x-layout>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="w-full max-w-4xl mx-auto pb-12">

    <!-- Ambient glow decoration -->
    <div class="absolute top-0 right-1/4 w-96 h-96 rounded-full bg-emerald-500/5 blur-[120px] pointer-events-none z-0"></div>

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-8 pt-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 dark:bg-emerald-400/10 px-3 py-1 rounded-full border border-emerald-500/20">My Profile</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">Profile Settings</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Manage your account details, farm location, and cooperative affiliation.</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Verification Badge -->
                    @if($profile?->is_verified)
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 rounded-full text-xs font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Verified
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-400 rounded-full text-xs font-bold">
                            Pending Verification
                        </span>
                    @endif
                </div>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-6 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session('password_success'))
            <div class="mb-6 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('password_success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 bg-red-500/10 border border-red-500/20 text-red-700 dark:text-red-400 rounded-2xl p-5 text-sm font-semibold">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════ --}}
        {{-- PROFILE FORM --}}
        {{-- ═══════════════════════════════════════════ --}}
        <form action="{{ route('profile.update') }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            {{-- ── ACCOUNT INFORMATION ── --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Account Information</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Your login credentials and display name.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Full Name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition text-sm text-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition text-sm text-slate-800 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- ── FARM DETAILS ── --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/15 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Farm Details</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Contact number and farm location.</p>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Phone Number</label>
                            <input type="text" name="phone" value="{{ old('phone', $profile->phone ?? '') }}"
                                class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition text-sm text-slate-800 dark:text-white">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Farm Location</label>
                            <input type="text" id="farm_location_display" name="farm_location" value="{{ old('farm_location', $profile->farm_location ?? '') }}" readonly
                                class="px-4 py-3 w-full bg-slate-50 dark:bg-slate-700/30 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none cursor-default text-sm text-slate-600 dark:text-slate-300 font-medium">
                        </div>
                    </div>

                    {{-- Hidden coordinate inputs --}}
                    <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $profile->latitude ?? '') }}">
                    <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', $profile->longitude ?? '') }}">

                    {{-- GPS button --}}
                    <button type="button" id="use-my-location" class="w-full flex items-center justify-center gap-2 py-2.5 bg-emerald-500/5 hover:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 rounded-xl text-xs font-bold transition shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Use My GPS Location
                    </button>

                    {{-- Map --}}
                    <div id="farm-map" class="w-full h-[250px] rounded-xl border border-emerald-500/15 shadow-sm overflow-hidden z-0"></div>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium text-center">
                        Drag the pin or click the map to update your farm location.
                    </p>
                </div>
            </div>

            {{-- ── COOPERATIVE AFFILIATION ── --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/15 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Cooperative Affiliation</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Your current membership type and cooperative assignment.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    {{-- Affiliation Type Badge --}}
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Membership Type:</span>
                        @if($profile?->affiliation_type === 'cooperative')
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-400 rounded-lg text-xs font-bold">
                                Cooperative Member
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-400 rounded-lg text-xs font-bold">
                                Independent Farmer
                            </span>
                        @endif
                    </div>

                    @if($profile?->affiliation_type === 'cooperative')
                        <div>
                            <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Select Cooperative</label>
                            <div class="relative">
                                <select name="cooperative_id"
                                    class="pl-4 pr-10 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition appearance-none cursor-pointer text-sm text-slate-700 dark:text-slate-200">
                                    <option value="">— No cooperative selected —</option>
                                    @foreach($cooperatives as $coop)
                                        <option value="{{ $coop->id }}"
                                            {{ old('cooperative_id', $profile->cooperative_id ?? '') == $coop->id ? 'selected' : '' }}>
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
                            @if($cooperatives->isEmpty())
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 font-medium bg-slate-50 dark:bg-slate-700/30 p-2.5 rounded-lg border border-slate-200/50 dark:border-slate-600/40">
                                    No verified cooperatives are registered yet.
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium bg-slate-50 dark:bg-slate-700/30 p-3 rounded-lg border border-slate-200/50 dark:border-slate-600/40">
                            You are registered as an independent farmer. Contact your cooperative administrator to change your affiliation type.
                        </p>
                    @endif
                </div>
            </div>

            {{-- SAVE BUTTON --}}
            <div class="flex justify-end">
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:brightness-105 text-white font-bold rounded-xl text-sm shadow-md shadow-emerald-600/15 hover:shadow-lg transition duration-200 transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- PASSWORD CHANGE --}}
        {{-- ═══════════════════════════════════════════ --}}
        <form action="{{ route('profile.password') }}" method="POST" class="mt-8">
            @csrf
            @method('PUT')

            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-violet-500/10 border border-violet-500/15 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Change Password</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Update your login password. Requires current password.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Current Password</label>
                        <input type="password" name="current_password" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">New Password</label>
                        <input type="password" name="password" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Confirm New Password</label>
                        <input type="password" name="password_confirmation" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition text-sm">
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="px-6 py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl text-xs shadow-md shadow-violet-600/15 transition duration-200 cursor-pointer inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        Update Password
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>

{{-- ═══════════════════════════════════════════ --}}
{{-- LEAFLET MAP SCRIPT --}}
{{-- ═══════════════════════════════════════════ --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const GENSAN = [6.1164, 125.1716];
        const existingLat = {{ $profile->latitude ?? 'null' }};
        const existingLng = {{ $profile->longitude ?? 'null' }};
        const initPos = (existingLat && existingLng) ? [existingLat, existingLng] : GENSAN;
        const initZoom = (existingLat && existingLng) ? 15 : 13;

        const map = L.map('farm-map', { zoomControl: true }).setView(initPos, initZoom);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '© OpenStreetMap © CARTO',
            subdomains: 'abcd',
            maxZoom: 19,
        }).addTo(map);

        const greenIcon = L.divIcon({
            html: `<div style="
                width: 18px; height: 18px; border-radius: 50%;
                background: #2D6A2F; border: 3px solid white;
                box-shadow: 0 3px 8px rgba(45, 106, 47, 0.4);
            "></div>`,
            className: '',
            iconAnchor: [9, 9],
        });

        const marker = L.marker(initPos, { draggable: true, icon: greenIcon }).addTo(map);

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

        function updateCoords(latlng) {
            document.getElementById('latitude').value  = latlng.lat.toFixed(8);
            document.getElementById('longitude').value = latlng.lng.toFixed(8);
            reverseGeocode(latlng.lat, latlng.lng);
        }

        marker.on('dragend', function (e) { updateCoords(e.target.getLatLng()); });
        map.on('click', function (e) { marker.setLatLng(e.latlng); updateCoords(e.latlng); });

        document.getElementById('use-my-location').addEventListener('click', function () {
            if (!navigator.geolocation) { alert('Geolocation not supported.'); return; }
            const btn = this;
            btn.textContent = 'Locating...';
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
                    alert('Unable to retrieve location. Pin manually.');
                    btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Use My GPS Location`;
                }
            );
        });

        // Fix Leaflet map rendering in hidden containers
        setTimeout(() => map.invalidateSize(), 200);
    });
</script>

</x-layout>
