<x-layout>
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />

<div class="w-full max-w-4xl mx-auto pb-12">

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-8 pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#16283C] dark:text-[#D7BC7A] bg-[#16283C]/10 dark:bg-[#16283C]/10 px-3 py-1 rounded-full border border-[#16283C]/20">My Profile</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">Profile Settings</h1>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Profile Verification Badge -->
                    @if($profile?->is_verified)
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[#16283C]/10 border border-[#16283C]/20 text-[#16283C] dark:text-[#D7BC7A] rounded-full text-xs font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Profile Verified
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] text-[var(--color-warning-text)] rounded-full text-xs font-bold">
                            Profile Pending
                        </span>
                    @endif

                    <!-- Email Verification Badge -->
                    @if($user->hasVerifiedEmail())
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[var(--color-info-bg)] border border-[var(--color-info-border)] text-[var(--color-info-text)] rounded-full text-xs font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Email Verified
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] text-[var(--color-warning-text)] rounded-full text-xs font-bold">
                            Email Unverified
                        </span>
                    @endif
                </div>
            </div>
        </header>

        {{-- Flash Messages --}}
        <x-flash-success />

        @if(session('password_success'))
            <x-flash-success :message="session('password_success')" />
        @endif

        @if ($errors->any())
            <div class="mb-6 bg-red-500/10 dark:bg-red-500/10 border border-red-500/20 dark:border-red-500/30 text-red-700 dark:text-red-400 rounded-2xl p-5 text-sm font-semibold">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('profile_complete'))
            <div class="mb-6 p-5 rounded-2xl bg-gradient-to-r from-[#16283C]/10 to-[#0E1620]/5 dark:from-[#16283C]/20 dark:to-[#0E1620]/10 border border-[#16283C]/20 dark:border-[#16283C]/30 text-brand-dark dark:text-brand-light">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-[10px] bg-[#16283C] flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold mb-0.5">Welcome to HarvestHaul!</h3>
                        <p class="text-xs text-[#4a6a4a] dark:text-[#6a9a6a] leading-relaxed font-medium">Please complete your business details below to start managing fleet and coordinating shipments.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════ --}}
        {{-- PROFILE FORM --}}
        {{-- ═══════════════════════════════════════════ --}}
        <form action="{{ route('profile.update') }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            {{-- ── ACCOUNT INFORMATION ── --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-[#16283C]/10 border border-[#16283C]/15 flex items-center justify-center text-[#16283C] dark:text-[#D7BC7A] shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Account Information</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Your login credentials and representative name.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="name" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Representative Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C] transition text-sm text-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label for="email" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Email Address</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C] transition text-sm text-slate-800 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- ── BUSINESS DETAILS ── --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-[#0E1620]/10 border border-[#0E1620]/15 flex items-center justify-center text-[#0E1620] dark:text-[#E9EEF4] shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Business Details</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Company information and contact number.</p>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="company_name" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Company / Cooperative Name</label>
                            <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $profile->company_name ?? '') }}" required
                                class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0E1620]/20 focus:border-[#0E1620] transition text-sm text-slate-800 dark:text-white">
                        </div>
                        <div>
                            <label for="phone" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Phone Number</label>
                            <input type="tel" inputmode="tel" id="phone" name="phone" value="{{ old('phone', $profile->phone ?? '') }}" required
                                class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0E1620]/20 focus:border-[#0E1620] transition text-sm text-slate-800 dark:text-white">
                        </div>
                    </div>

                    {{-- Logistics Type Badge --}}
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Organization Type:</span>
                        @if($profile?->logistics_type === 'cooperative')
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-[#16283C]/10 border border-[#16283C]/20 text-[#16283C] dark:text-[#D7BC7A] rounded-lg text-xs font-bold">
                                Cooperative
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-[#0E1620]/10 border border-[#0E1620]/20 text-[#0E1620] dark:text-[#E9EEF4] rounded-lg text-xs font-bold">
                                 Private Company
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── LOCATION (COOPERATIVE ONLY) ── --}}
            @if($profile?->logistics_type === 'cooperative')
                <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-2xl bg-harvest/10 border border-harvest/15 flex items-center justify-center text-harvest-dark dark:text-harvest-light shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Cooperative Location</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Used as the fixed drop-off point when finalizing deals with your member farmers.</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label for="office_address" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Drop-off Point Name / Address</label>
                            <input type="text" id="office_address" name="office_address" value="{{ old('office_address', $profile->office_address ?? '') }}"
                                placeholder="e.g. Coop Warehouse, Dadiangas"
                                class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C] transition text-sm text-slate-800 dark:text-white">
                        </div>

                        <button type="button" id="use-my-location"
                            class="mb-3 w-full flex items-center justify-center gap-2 py-2.5 bg-[#16283C]/5 hover:bg-[#16283C]/10 text-[#16283C] dark:text-[#D7BC7A] border border-[#16283C]/20 rounded-xl text-xs font-bold transition shadow-sm">
                            Use My Current Location
                        </button>

                        <div>
                            <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Pin Location on Map</label>
                            <div id="coop-map" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden" style="height: 300px;"></div>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1.5 italic">Click the map or drag the pin to set your cooperative's drop-off coordinates.</p>
                        </div>

                        <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $profile->latitude ?? '') }}">
                        <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', $profile->longitude ?? '') }}">

                        @if(!$profile->latitude || !$profile->longitude)
                            <div class="p-4 bg-amber-500/10 dark:bg-amber-500/10 border-l-4 border-amber-500 rounded-r-xl">
                                <p class="text-xs font-semibold text-amber-800 dark:text-amber-400 leading-relaxed">
                                    No location pinned yet. Without it, you cannot use the fixed drop-off option when finalizing a deal — you would need to pin a custom destination each time.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ── REGULATORY CREDENTIALS ── --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/15 flex items-center justify-center text-amber-700 dark:text-amber-400 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Regulatory Credentials</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Business permits and registration numbers for compliance.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="business_permit_no" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Business Permit Number</label>
                        <input type="text" id="business_permit_no" name="business_permit_no" value="{{ old('business_permit_no', $profile->business_permit_no ?? '') }}"
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition text-sm text-slate-800 dark:text-white"
                            placeholder="e.g. BP-2026-XXXXX">
                    </div>

                    @if($profile?->logistics_type === 'cooperative')
                        <div>
                            <label for="cda_registration_no" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">CDA Registration Number</label>
                            <input type="text" id="cda_registration_no" name="cda_registration_no" value="{{ old('cda_registration_no', $profile->cda_registration_no ?? '') }}"
                                class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition text-sm text-slate-800 dark:text-white"
                                placeholder="e.g. CDA-XXXXX">
                        </div>
                    @else
                        <div class="flex items-end">
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium bg-slate-50 dark:bg-slate-700/30 p-3 rounded-lg border border-slate-200/50 dark:border-slate-600/40 w-full">
                                CDA Registration is only applicable for cooperatives.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- CONFIRM PASSWORD --}}
            <x-current-password-field />

            {{-- SAVE BUTTON --}}
            <div class="flex justify-end">
                <x-button type="submit" size="lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Changes
                </x-button>
            </div>
        </form>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- PASSWORD CHANGE --}}
        {{-- ═══════════════════════════════════════════ --}}
        <form action="{{ route('profile.password') }}" method="POST" class="mt-8">
            @csrf
            @method('PUT')

            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-brand/10 border border-brand/15 flex items-center justify-center text-brand dark:text-brand-light shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Change Password</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Update your login password. Requires current password.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label for="current_password" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition text-sm">
                    </div>
                    <div>
                        <label for="password" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">New Password</label>
                        <input type="password" id="password" name="password" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition text-sm">
                    </div>
                    <div>
                        <label for="password_confirmation" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Confirm New Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition text-sm">
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="px-6 py-2.5 bg-brand hover:bg-brand-dark text-white font-bold rounded-xl text-xs shadow-md shadow-brand/15 transition duration-200 cursor-pointer inline-flex items-center gap-2">
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

@if($profile?->logistics_type === 'cooperative')
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const GENSAN = [6.1164, 125.1716];
            const existingLat = {{ $profile->latitude ?? 'null' }};
            const existingLng = {{ $profile->longitude ?? 'null' }};
            const initPos = (existingLat && existingLng) ? [existingLat, existingLng] : GENSAN;
            const initZoom = (existingLat && existingLng) ? 15 : 13;

            const map = L.map('coop-map', { zoomControl: true }).setView(initPos, initZoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: ' OpenStreetMap contributors' }).addTo(map);

            const greenIcon = L.divIcon({
                html: `<div style="
                    width: 18px; height: 18px; border-radius: 50%;
                    background: #16283C; border: 3px solid white;
                    box-shadow: 0 3px 8px rgba(45, 106, 47, 0.4);
                "></div>`,
                className: '',
                iconAnchor: [9, 9],
            });

            const marker = L.marker(initPos, { draggable: true, icon: greenIcon }).addTo(map);

            function updateCoords(latlng) {
                document.getElementById('latitude').value = latlng.lat.toFixed(8);
                document.getElementById('longitude').value = latlng.lng.toFixed(8);
            }

            marker.on('dragend', function (e) { updateCoords(e.target.getLatLng()); });
            map.on('click', function (e) { marker.setLatLng(e.latlng); updateCoords(e.latlng); });

            document.getElementById('use-my-location').addEventListener('click', function () {
                if (!navigator.geolocation) { Swal.fire({ icon: 'error', title: 'Geolocation not supported', text: 'Your browser does not support location services. Pin your location manually.', confirmButtonColor: '#16283C', background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff', color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b', customClass: { popup: 'rounded-xl' } }); return; }
                navigator.geolocation.getCurrentPosition(
                    function (pos) {
                        const latlng = L.latLng(pos.coords.latitude, pos.coords.longitude);
                        marker.setLatLng(latlng);
                        updateCoords(latlng);
                        map.setView(latlng, 16);
                    },
                    function () { Swal.fire({ icon: 'error', title: 'Could not get location', text: 'Unable to retrieve your location. Pin it manually on the map.', confirmButtonColor: '#16283C', background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff', color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b', customClass: { popup: 'rounded-xl' } }); }
                );
            });

            setTimeout(() => map.invalidateSize(), 200);
        });
    </script>
@endif

</x-layout>
