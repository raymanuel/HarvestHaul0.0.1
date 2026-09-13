@props(['customer' => null])

@php
    $isEdit = !is_null($customer);
    $title = $isEdit ? 'Edit Customer' : 'Add Customer';
@endphp

<x-layout :title="$title">
    @push('head')
        <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
    @endpush
    @push('scripts')
        <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    @endpush

    <div class="w-full max-w-2xl mx-auto pb-12">

        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <a href="{{ route('coop.customers.index') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mb-4 inline-block font-semibold transition">
                ← Back to Customers
            </a>
            <span class="text-xs font-bold uppercase tracking-wider text-harvest-dark dark:text-harvest-light bg-harvest/10 dark:bg-harvest/20 px-3 py-1.5 rounded-md border border-harvest/10 dark:border-harvest/20 inline-block mb-2">Outbound Distribution</span>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">{{ $title }}</h1>
        </header>

        <x-flash-success />
        <x-flash-error />

        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
            <form method="POST" action="{{ $isEdit ? route('coop.customers.update', $customer) : route('coop.customers.store') }}">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="space-y-5">
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Customer Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $customer->name ?? '') }}" required placeholder="e.g. KCC Mart de GenSan"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 focus:border-[#16283C] transition">
                        @error('name')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="business_type" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                                Business Type
                            </label>
                            <select name="business_type" id="business_type"
                                class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 focus:border-[#16283C] transition">
                                <option value="">Select Type</option>
                                <option value="grocery" {{ (old('business_type', $customer->business_type ?? '') === 'grocery') ? 'selected' : '' }}>Grocery / Retail</option>
                                <option value="restaurant" {{ (old('business_type', $customer->business_type ?? '') === 'restaurant') ? 'selected' : '' }}>Restaurant</option>
                                <option value="processor" {{ (old('business_type', $customer->business_type ?? '') === 'processor') ? 'selected' : '' }}>Processor</option>
                                <option value="wholesaler" {{ (old('business_type', $customer->business_type ?? '') === 'wholesaler') ? 'selected' : '' }}>Wholesaler</option>
                            </select>
                            @error('business_type')
                                <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="contact" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                                Contact
                            </label>
                            <input type="text" name="contact" id="contact" value="{{ old('contact', $customer->contact ?? '') }}" placeholder="e.g. 09171234567"
                                class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 focus:border-[#16283C] transition">
                            @error('contact')
                                <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="address" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Address
                        </label>
                        <input type="text" name="address" id="address" value="{{ old('address', $customer->address ?? '') }}" placeholder="Street, Barangay, City"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 focus:border-[#16283C] transition">
                        @error('address')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Map Location
                        </label>
                        <div id="customer-map" class="w-full h-[220px] rounded-xl border border-slate-200 dark:border-slate-600 overflow-hidden z-0"></div>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium mt-1.5" id="customer-map-hint">Tap the map or drag the pin to set the customer's location.</p>
                        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $customer->latitude ?? '') }}">
                        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $customer->longitude ?? '') }}">
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <x-button type="submit" size="lg">
                            {{ $isEdit ? 'Save Changes' : 'Add Customer' }}
                        </x-button>
                        <a href="{{ route('coop.customers.index') }}"
                            class="text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-slate-600 dark:hover:text-slate-400 px-4 py-3.5 rounded-xl border border-slate-200/60 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900/30 transition">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const GENSAN = [{{ old('latitude', $customer->latitude ?? '') ? old('latitude', $customer->latitude ?? '') : '6.1164' }}, {{ old('longitude', $customer->longitude ?? '') ? old('longitude', $customer->longitude ?? '') : '125.1716' }}];
                const existingLat = parseFloat(document.getElementById('latitude').value);
                const existingLng = parseFloat(document.getElementById('longitude').value);
                const initPos = (existingLat && existingLng) ? [existingLat, existingLng] : GENSAN;
                const initZoom = (existingLat && existingLng) ? 15 : 13;

                const map = L.map('customer-map', { zoomControl: true }).setView(initPos, initZoom);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: ' OpenStreetMap contributors' }).addTo(map);

                const marker = L.marker(initPos, { draggable: true }).addTo(map);

                function reverseGeocode(lat, lng) {
                    fetch('https://nominatim.openstreetmap.org/reverse?lat=' + lat + '&lon=' + lng + '&format=json')
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (data && data.display_name) {
                                const addr = data.address;
                                const parts = [
                                    addr.road || addr.village || addr.suburb || addr.neighbourhood,
                                    addr.city || addr.town || addr.municipality,
                                    addr.province || addr.state,
                                ].filter(Boolean);
                                document.getElementById('address').value = parts.length ? parts.join(', ') : data.display_name;
                            }
                        })
                        .catch(function () {});
                }

                function updateCoords(latlng) {
                    document.getElementById('latitude').value = latlng.lat.toFixed(8);
                    document.getElementById('longitude').value = latlng.lng.toFixed(8);
                    document.getElementById('customer-map-hint').textContent = 'Pinned at ' + latlng.lat.toFixed(5) + ', ' + latlng.lng.toFixed(5);
                    reverseGeocode(latlng.lat, latlng.lng);
                }

                marker.on('dragend', function (e) { updateCoords(e.target.getLatLng()); });
                map.on('click', function (e) { marker.setLatLng(e.latlng); updateCoords(e.latlng); });

                setTimeout(function () { map.invalidateSize(); }, 200);
            });
        </script>
    @endpush

</x-layout>