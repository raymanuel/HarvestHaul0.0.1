<x-layout>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<div class="w-full max-w-2xl">

    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:text-slate-400 dark:hover:text-slate-300 mb-4 inline-block font-semibold">
            ← Back to Dashboard
        </a>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Post New Harvest</h1>
        <p class="text-slate-500 dark:text-slate-400 font-medium">Fill in your crop details. Once posted, your farm will appear on the logistics map for pickup.</p>
    </header>

    @php $missingLocation = !Auth::user()->farmerProfile?->latitude || !Auth::user()->farmerProfile?->longitude; @endphp
    @if ($missingLocation)
        <div class="mb-6 bg-red-50 border-2 border-red-300 dark:bg-red-950/30 dark:border-red-500/30 rounded-xl px-5 py-5 flex gap-3 items-start shadow-sm">
            <span class="text-xl mt-0.5"><x-icon name="pin" class="w-4 h-4" /></span>
            <div>
                <p class="text-sm font-bold text-red-800 dark:text-red-300">Farm location required</p>
                <p class="text-xs text-red-600 dark:text-red-400 mt-1 leading-relaxed">
                    You must set your farm's GPS coordinates before posting a harvest.
                    Buyers need your location to negotiate and schedule pickup.
                </p>
                <a href="{{ route('profile.show') }}" class="mt-3 inline-block text-xs font-bold text-white bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg transition">
                    Go to Profile → Farm Location
                </a>
            </div>
        </div>
    @endif

    {{-- PRIORITY 5: Independent Farmer Logistics Warning --}}
    @if (isset($isIndependent) && $isIndependent && !$hasCommercialLogistics)
        <div class="mb-6 bg-orange-50 border border-orange-200 dark:bg-orange-950/20 dark:border-orange-500/20 rounded-xl px-5 py-4 shadow-sm">
            <div>
                <p class="font-bold text-sm text-orange-800 dark:text-orange-300">Limited Transport Availability</p>
                <p class="text-sm text-orange-700 dark:text-orange-400 mt-0.5 leading-relaxed font-medium">
                    There are currently <strong>no verified commercial logistics partners</strong> active on the network.
                    You may still post your harvest, but please be aware that pickup scheduling may be delayed until a partner becomes available.
                </p>
            </div>
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 dark:bg-red-950/20 dark:border-red-500/20 text-red-700 dark:text-red-400 rounded-xl px-5 py-4 text-sm font-semibold">
            <p class="font-semibold mb-1">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm p-8">
            <form method="POST" action="{{ route('harvests.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- Crop --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Crop Type <span class="text-red-400">*</span>
                </label>
                <select
                    name="crop_id"
                    id="crop_id"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition"
                    onchange="handleCropChange(this.value)"
                    required
                >
                    <option value="" disabled {{ old('crop_id') ? '' : 'selected' }}>Select a crop</option>
                    @foreach ($crops as $crop)
                        <option value="{{ $crop->id }}" {{ old('crop_id') == $crop->id ? 'selected' : '' }}>
                            {{ $crop->name }}
                        </option>
                    @endforeach
                    <option value="other" {{ old('crop_id') === 'other' ? 'selected' : '' }}>Other (type manually)</option>
                </select>
                <input
                    type="text"
                    name="custom_crop_name"
                    id="custom_crop_name"
                    value="{{ old('custom_crop_name') }}"
                    placeholder="Enter crop name (e.g. Dragon Fruit)"
                    class="mt-2 w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition {{ old('crop_id') === 'other' ? '' : 'hidden' }}"
                />
                @error('crop_id')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
                @error('custom_crop_name')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Variety (cascading) --}}
            <div class="mb-6" id="variety_wrapper" style="{{ old('crop_id') ? '' : 'display:none;' }}">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Variety <span class="text-red-400">*</span>
                </label>
                <select
                    name="crop_variety_id"
                    id="crop_variety_id"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition"
                    onchange="handleVarietyChange(this.value)"
                >
                    <option value="" disabled selected>Select a variety</option>
                </select>
                <input
                    type="text"
                    name="custom_variety_name"
                    id="custom_variety_name"
                    value="{{ old('custom_variety_name') }}"
                    placeholder="Enter variety name (e.g. Red Lady)"
                    class="mt-2 w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition {{ old('crop_variety_id') === 'other' ? '' : 'hidden' }}"
                />
                @error('crop_variety_id')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
                @error('custom_variety_name')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Quantity --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Estimated Quantity (kg) <span class="text-red-400">*</span>
                </label>
                <input
                    type="number"
                    name="quantity_kg"
                    id="quantity_kg"
                    value="{{ old('quantity_kg') }}"
                    placeholder="e.g. 500"
                    min="0.01"
                    max="999999.99"
                    step="0.01"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition"
                    oninput="validateQuantity(this)"
                    required
                />
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500 font-medium">Estimated harvest weight. Actual weight confirmed at pickup.</p>
                <p id="quantity_warning" class="hidden mt-2 text-xs text-amber-600 dark:text-amber-400 font-bold">
                    <x-icon name="warning" class="w-4 h-4" /> That quantity seems unrealistic. Max allowed is 999,999.99 kg.
                </p>
                @error('quantity_kg')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Suggested Price --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Your Suggested Price (₱/kg) <span class="text-slate-400 dark:text-slate-500 font-normal">(optional)</span>
                </label>
                <input
                    type="number"
                    inputmode="numeric"
                    name="suggested_price_per_kg"
                    id="suggested_price_per_kg"
                    value="{{ old('suggested_price_per_kg') }}"
                    placeholder="e.g. 45"
                    min="0"
                    max="99999"
                    step="1"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition"
                />
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500 font-medium">Buyers will see this as your asking price. Leave blank if open to negotiation.</p>
                @error('suggested_price_per_kg')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
                <div id="market-reference" class="hidden mt-3 bg-[#3A7D44]/5 dark:bg-[#3A7D44]/10 border border-[#3A7D44]/15 dark:border-[#3A7D44]/20 rounded-xl px-4 py-3">
                    <p class="text-[11px] font-bold text-[#3A7D44] dark:text-[#3A7D44] uppercase tracking-wider mb-1">Market Reference (DA RFO12)</p>
                    <p id="market-reference-text" class="text-xs text-slate-600 dark:text-slate-400"></p>
                </div>
            </div>

            {{-- Harvest Date --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Harvest Date <span class="text-slate-400 dark:text-slate-500 font-normal">(optional)</span>
                </label>
                <input
                    type="date"
                    name="harvest_date"
                    value="{{ old('harvest_date') }}"
                    max="{{ date('Y-m-d', strtotime('+1 day')) }}"
                    min="{{ date('Y-m-d') }}"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition"
                />
                @error('harvest_date')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Crop Photos --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Crop Photos <span class="text-slate-400 dark:text-slate-500 font-normal">(optional, max 5)</span>
                </label>
                <input
                    type="file"
                    name="crop_photos[]"
                    id="crop_photos"
                    multiple
                    accept="image/*"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#2D8A37] file:text-white hover:file:bg-opacity-90 transition"
                />
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500 font-medium">Upload photos of your crop to attract buyers. Max 5 images, 5MB each.</p>
                @error('crop_photos.*')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Notes --}}
            <div class="mb-8">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Pickup Notes <span class="text-slate-400 dark:text-slate-500 font-normal">(optional)</span>
                </label>
                <textarea
                    name="notes"
                    rows="4"
                    placeholder="e.g. Use the side gate, available after 8am, call before arrival"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent resize-none transition"
                >{{ old('notes') }}</textarea>
            </div>

            {{-- Destination --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Delivery Destination <span class="text-red-400">*</span>
                </label>
                <select
                    name="destination_id"
                    id="destination_id"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition"
                    onchange="handleDestinationChange(this.value)"
                    required
                >
                    <option value="" disabled {{ old('destination_id') ? '' : 'selected' }}>— Select a destination —</option>
                    @foreach ($destinations as $destination)
                        <option
                            value="{{ $destination->id }}"
                            data-lat="{{ $destination->latitude }}"
                            data-lng="{{ $destination->longitude }}"
                            data-address="{{ $destination->address }}"
                            {{ old('destination_id') == $destination->id ? 'selected' : '' }}
                        >
                            {{ $destination->name }} ({{ ucfirst(str_replace('_', ' ', $destination->type)) }})
                        </option>
                    @endforeach
                    <option value="custom" {{ old('destination_id') === 'custom' ? 'selected' : '' }}>
                        <x-icon name="pin" class="w-4 h-4" /> Custom Location — Pin on Map
                    </option>
                </select>
                @error('destination_id')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
                @error('destination_latitude')
                    <p class="mt-2 text-xs text-red-500">Please pin a destination on the map.</p>
                @enderror
            </div>

            {{-- Custom Map Pin (hidden until "Custom Location" is selected) --}}
            <div id="custom_map_wrapper" class="mb-6 hidden">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Pin Your Destination</label>
                <p class="text-xs text-slate-400 dark:text-slate-500 mb-2">Click on the map to drop a pin on your delivery destination.</p>
                <div id="destination-map" class="w-full rounded-xl border border-slate-300 dark:border-slate-700" style="height: 300px;"></div>
                <p id="pin-feedback" class="text-xs text-slate-500 dark:text-slate-400 mt-2 italic">No pin placed yet.</p>
            </div>

            {{-- Hidden fields — always submitted --}}
            <input type="hidden" name="destination_address"   id="destination_address"   value="{{ old('destination_address') }}">
            <input type="hidden" name="destination_latitude"  id="destination_latitude"  value="{{ old('destination_latitude') }}">
            <input type="hidden" name="destination_longitude" id="destination_longitude" value="{{ old('destination_longitude') }}">

            {{-- Info Banner --}}
            <div class="mb-6 bg-green-50 border border-green-200 dark:bg-[#3A7D44]/10 dark:border-[#3A7D44]/20 rounded-xl px-4 py-3 flex gap-3 items-start">
                <span class="text-lg"><x-icon name="pin" class="w-4 h-4" /></span>
                <p class="text-sm text-green-700 dark:text-[#3A7D44] font-medium">
                    Once posted, your farm location will be <strong>visible on the logistics map</strong> for pickup coordination.
                    You can remove the post anytime from your dashboard.
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                @if($missingLocation)
                    <a href="{{ route('profile.show') }}"
                        class="flex-1 text-center bg-red-600 text-white font-bold py-3 rounded-xl hover:bg-red-700 transition shadow-md cursor-pointer">
                        Set Farm Location to Continue
                    </a>
                @else
                    <button type="submit" id="post-harvest-btn"
                        class="flex-1 bg-[#2D8A37] text-white font-bold py-3 rounded-xl hover:bg-opacity-90 transition shadow-md cursor-pointer">
                        Post Harvest
                    </button>
                @endif
                <a href="{{ route('dashboard') }}"
                    class="flex-1 text-center bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-bold py-3 rounded-xl transition">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<script>
    // Build variety map from DB — keyed by crop ID, values are {id, name} objects
    const cropVarieties = @json(
        $crops->mapWithKeys(fn($crop) => [
            $crop->id => $crop->varieties->map(fn($v) => ['id' => $v->id, 'name' => $v->name])
        ])
    );

    // Build crop name map for market price lookup
    const cropNames = @json($crops->mapWithKeys(fn($crop) => [$crop->id => $crop->name]));

    function handleCropChange(value) {
        const customInput = document.getElementById('custom_crop_name');
        const varietyWrapper = document.getElementById('variety_wrapper');
        const varietySelect = document.getElementById('crop_variety_id');
        const customVarietyInput = document.getElementById('custom_variety_name');

        if (value === 'other') {
            customInput.classList.remove('hidden');
            customInput.focus();
            varietyWrapper.style.display = 'block';
            varietySelect.innerHTML = '<option value="other">Other (type manually)</option>';
            varietySelect.value = 'other';
            handleVarietyChange('other');
            document.getElementById('market-reference').classList.add('hidden');
        } else {
            customInput.classList.add('hidden');
            customInput.value = '';
            updateVarieties(value);
            fetchMarketPrice(cropNames[value] || '');
        }
    }

    function fetchMarketPrice(cropName) {
        const ref = document.getElementById('market-reference');
        const text = document.getElementById('market-reference-text');
        if (!cropName) {
            ref.classList.add('hidden');
            return;
        }
        fetch('/api/market-price/' + encodeURIComponent(cropName))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.dpi) {
                    var range = data.low && data.high ? '₱' + data.low + '–' + data.high + '/kg' : '₱' + data.dpi + '/kg';
                    var avg = data.dpi ? ' (avg: ₱' + data.dpi + '/kg)' : '';
                    text.textContent = data.commodity + ': ' + range + avg + ' as of ' + data.date;
                    ref.classList.remove('hidden');
                } else {
                    ref.classList.add('hidden');
                }
            })
            .catch(function () {
                ref.classList.add('hidden');
            });
    }

    function handleVarietyChange(value) {
        const customInput = document.getElementById('custom_variety_name');

        if (value === 'other') {
            customInput.classList.remove('hidden');
            customInput.focus();
        } else {
            customInput.classList.add('hidden');
            customInput.value = '';
        }
    }

    function updateVarieties(cropId, selectedVarietyId = null) {
        const wrapper  = document.getElementById('variety_wrapper');
        const select   = document.getElementById('crop_variety_id');
        const customInput = document.getElementById('custom_variety_name');
        const varieties = cropVarieties[cropId] || [];

        select.innerHTML = '<option value="" disabled selected>Select a variety</option>';

        if (varieties.length) {
            varieties.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.textContent = v.name;
                if (selectedVarietyId && v.id == selectedVarietyId) opt.selected = true;
                select.appendChild(opt);
            });
            // Add "Other" option at the end
            const otherOpt = document.createElement('option');
            otherOpt.value = 'other';
            otherOpt.textContent = 'Other (type manually)';
            if (selectedVarietyId === 'other') otherOpt.selected = true;
            select.appendChild(otherOpt);
            wrapper.style.display = 'block';
            customInput.classList.add('hidden');
            customInput.value = '';
        } else {
            wrapper.style.display = 'none';
        }
    }

    function validateQuantity(input) {
        const warning = document.getElementById('quantity_warning');
        const value   = parseFloat(input.value);

        if (value > 999999.99 || value <= 0) {
            warning.classList.remove('hidden');
            input.classList.add('border-amber-400');
            input.classList.remove('border-slate-300', 'dark:border-slate-700');
        } else {
            warning.classList.add('hidden');
            input.classList.remove('border-amber-400');
            input.classList.add('border-slate-300', 'dark:border-slate-700');
        }
    }

    // Restore old() values on validation failure
    document.addEventListener('DOMContentLoaded', () => {
        const oldCropId   = "{{ old('crop_id') }}";
        const oldVarietyId = "{{ old('crop_variety_id') }}";

        if (oldCropId === 'other') {
            document.getElementById('crop_id').value = 'other';
            handleCropChange('other');
            document.getElementById('custom_crop_name').value = "{{ old('custom_crop_name') }}";
            if (oldVarietyId === 'other') {
                document.getElementById('custom_variety_name').value = "{{ old('custom_variety_name') }}";
            }
        } else if (oldCropId) {
            document.getElementById('crop_id').value = oldCropId;
            updateVarieties(oldCropId, oldVarietyId);
        }
    });


        // Leaflet map for custom destination pinning
        let destinationMap = null;
        let destinationPin = null;

        function handleDestinationChange(value) {
            const wrapper = document.getElementById('custom_map_wrapper');

            if (value === 'custom') {
                wrapper.classList.remove('hidden');

                // Init map only once
                if (!destinationMap) {
                    destinationMap = L.map('destination-map').setView([6.1164, 125.1716], 11);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(destinationMap);

                    destinationMap.on('click', function (e) {
                        if (destinationPin) destinationMap.removeLayer(destinationPin);
                        destinationPin = L.marker(e.latlng).addTo(destinationMap);

                        document.getElementById('destination_latitude').value  = e.latlng.lat;
                        document.getElementById('destination_longitude').value = e.latlng.lng;
                        document.getElementById('destination_address').value   = `Custom (${e.latlng.lat.toFixed(5)}, ${e.latlng.lng.toFixed(5)})`;
                        document.getElementById('pin-feedback').textContent    = `Pinned at ${e.latlng.lat.toFixed(5)}, ${e.latlng.lng.toFixed(5)}`;
                    });
                }

        // Invalidate size in case it rendered while hidden
        setTimeout(() => destinationMap.invalidateSize(), 100);

        // Clear predefined destination hidden values
        document.getElementById('destination_latitude').value  = '';
        document.getElementById('destination_longitude').value = '';
        document.getElementById('destination_address').value   = '';

    } else {
        wrapper.classList.add('hidden');

        // Fill hidden fields from the selected predefined destination
        const selected = document.getElementById('destination_id');
        const opt      = selected.options[selected.selectedIndex];

        document.getElementById('destination_latitude').value  = opt.dataset.lat;
        document.getElementById('destination_longitude').value = opt.dataset.lng;
        document.getElementById('destination_address').value   = opt.dataset.address;
    }
}

// Restore old() state on validation failure
document.addEventListener('DOMContentLoaded', () => {
    const oldDestId = "{{ old('destination_id') }}";
    if (oldDestId) handleDestinationChange(oldDestId);
});
</script>
</x-layout>
