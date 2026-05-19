<x-layout>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<div class="w-full max-w-2xl">

    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600 mb-4 inline-block">
            ← Back to Dashboard
        </a>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Post New Harvest</h1>
        <p class="text-gray-500">Fill in your crop details. Once posted, your farm will appear on the logistics map for pickup.</p>
    </header>

    {{-- No GPS location warning --}}
    @if (!Auth::user()->farmerProfile?->latitude || !Auth::user()->farmerProfile?->longitude)
        <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex gap-3 items-start">
            <span class="text-lg">⚠️</span>
            <p class="text-sm text-amber-700">
                Your farm location is not set. Your listing will not appear on the logistics map.
                Update your profile to add a GPS pin.
            </p>
        </div>
    @endif

    {{-- PRIORITY 5: Independent Farmer Logistics Warning --}}
    @if (isset($isIndependent) && $isIndependent && !$hasCommercialLogistics)
        <div class="mb-6 bg-orange-50 border border-orange-200 rounded-xl px-5 py-4 flex gap-3 items-start shadow-sm">
            <span class="text-lg mt-0.5">🚚</span>
            <div>
                <p class="font-bold text-sm text-orange-800">Limited Transport Availability</p>
                <p class="text-sm text-orange-700 mt-0.5 leading-relaxed">
                    There are currently <strong>no verified commercial logistics partners</strong> active on the network.
                    You may still post your harvest, but please be aware that pickup scheduling may be delayed until a partner becomes available.
                </p>
            </div>
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4 text-sm">
            <p class="font-semibold mb-1">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
        <form method="POST" action="{{ route('harvests.store') }}">
            @csrf

            {{-- Crop --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Crop Type <span class="text-red-400">*</span>
                </label>
                <select
                    name="crop_id"
                    id="crop_id"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent"
                    onchange="updateVarieties(this.value)"
                    required
                >
                    <option value="" disabled {{ old('crop_id') ? '' : 'selected' }}>Select a crop</option>
                    @foreach ($crops as $crop)
                        <option value="{{ $crop->id }}" {{ old('crop_id') == $crop->id ? 'selected' : '' }}>
                            {{ $crop->name }}
                        </option>
                    @endforeach
                </select>
                @error('crop_id')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Variety (cascading) --}}
            <div class="mb-6" id="variety_wrapper" style="{{ old('crop_id') ? '' : 'display:none;' }}">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Variety <span class="text-red-400">*</span>
                </label>
                <select
                    name="crop_variety_id"
                    id="crop_variety_id"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent"
                >
                    <option value="" disabled selected>Select a variety</option>
                </select>
                @error('crop_variety_id')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Quantity --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
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
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition"
                    oninput="validateQuantity(this)"
                    required
                />
                <p class="mt-1 text-xs text-gray-400">Estimated harvest weight. Actual weight confirmed at pickup.</p>
                <p id="quantity_warning" class="hidden mt-2 text-xs text-amber-600">
                    ⚠ That quantity seems unrealistic. Max allowed is 999,999.99 kg.
                </p>
                @error('quantity_kg')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Harvest Date --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Harvest Date <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input
                    type="date"
                    name="harvest_date"
                    value="{{ old('harvest_date') }}"
                    max="{{ date('Y-m-d') }}"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition"
                />
                @error('harvest_date')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Quality Grade --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Quality Grade <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <select
                    name="quality_grade"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent"
                >
                    <option value="" {{ old('quality_grade') ? '' : 'selected' }}>— Select grade —</option>
                    <option value="Grade AA" {{ old('quality_grade') === 'Grade AA' ? 'selected' : '' }}>Grade AA — Export Quality</option>
                    <option value="Grade A"  {{ old('quality_grade') === 'Grade A'  ? 'selected' : '' }}>Grade A — Commercial Quality</option>
                    <option value="Grade B"  {{ old('quality_grade') === 'Grade B'  ? 'selected' : '' }}>Grade B — Local Market Quality</option>
                    <option value="Reject / Processing Grade" {{ old('quality_grade') === 'Reject / Processing Grade' ? 'selected' : '' }}>Reject / Processing Grade</option>
                </select>
                @error('quality_grade')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Packaging Type --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Packaging Type <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <select
                    name="packaging_type"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent"
                >
                    <option value="" {{ old('packaging_type') ? '' : 'selected' }}>— Select packaging —</option>
                    <option value="Bulk / Loose"                    {{ old('packaging_type') === 'Bulk / Loose'                    ? 'selected' : '' }}>Bulk / Loose</option>
                    <option value="Bamboo/Rattan Basket"            {{ old('packaging_type') === 'Bamboo/Rattan Basket'            ? 'selected' : '' }}>Bamboo / Rattan Basket</option>
                    <option value="Plastic Crate"                   {{ old('packaging_type') === 'Plastic Crate'                   ? 'selected' : '' }}>Plastic Crate</option>
                    <option value="Corrugated Fibreboard Box (CFB)" {{ old('packaging_type') === 'Corrugated Fibreboard Box (CFB)' ? 'selected' : '' }}>Corrugated Fibreboard Box (CFB)</option>
                    <option value="Plastic Sack / Net Bag"          {{ old('packaging_type') === 'Plastic Sack / Net Bag'          ? 'selected' : '' }}>Plastic Sack / Net Bag</option>
                    <option value="Styrofoam Box"                   {{ old('packaging_type') === 'Styrofoam Box'                   ? 'selected' : '' }}>Styrofoam Box</option>
                </select>
                @error('packaging_type')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Notes --}}
            <div class="mb-8">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Pickup Notes <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <textarea
                    name="notes"
                    rows="4"
                    placeholder="e.g. Use the side gate, available after 8am, call before arrival"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent resize-none"
                >{{ old('notes') }}</textarea>
            </div>

            {{-- Destination --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Delivery Destination <span class="text-red-400">*</span>
                </label>
                <select
                    name="destination_id"
                    id="destination_id"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent"
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
                        📍 Custom Location — Pin on Map
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
                <label class="block text-sm font-semibold text-gray-700 mb-2">Pin Your Destination</label>
                <p class="text-xs text-gray-400 mb-2">Click on the map to drop a pin on your delivery destination.</p>
                <div id="destination-map" class="w-full rounded-xl border border-gray-300" style="height: 300px;"></div>
                <p id="pin-feedback" class="text-xs text-gray-500 mt-2 italic">No pin placed yet.</p>
            </div>

            {{-- Hidden fields — always submitted --}}
            <input type="hidden" name="destination_address"   id="destination_address"   value="{{ old('destination_address') }}">
            <input type="hidden" name="destination_latitude"  id="destination_latitude"  value="{{ old('destination_latitude') }}">
            <input type="hidden" name="destination_longitude" id="destination_longitude" value="{{ old('destination_longitude') }}">

            {{-- Info Banner --}}
            <div class="mb-6 bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex gap-3 items-start">
                <span class="text-lg">📍</span>
                <p class="text-sm text-green-700">
                    Once posted, your farm location will be <strong>visible on the logistics map</strong> for pickup coordination.
                    You can remove the listing anytime from your dashboard.
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <button type="submit"
                    class="flex-1 bg-[#2D8A37] text-white font-bold py-3 rounded-xl hover:bg-opacity-90 transition shadow-md">
                    Post Listing
                </button>
                <a href="{{ route('dashboard') }}"
                    class="flex-1 text-center bg-slate-100 text-slate-700 font-bold py-3 rounded-xl hover:bg-slate-200 transition">
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

    function updateVarieties(cropId) {
        const wrapper  = document.getElementById('variety_wrapper');
        const select   = document.getElementById('crop_variety_id');
        const varieties = cropVarieties[cropId] || [];

        select.innerHTML = '<option value="" disabled selected>Select a variety</option>';

        if (varieties.length) {
            varieties.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.textContent = v.name;
                select.appendChild(opt);
            });
            wrapper.style.display = 'block';
            select.required = true;
        } else {
            wrapper.style.display = 'none';
            select.required = false;
        }
    }

    function validateQuantity(input) {
        const warning = document.getElementById('quantity_warning');
        const value   = parseFloat(input.value);

        if (value > 999999.99 || value <= 0) {
            warning.classList.remove('hidden');
            input.classList.add('border-amber-400');
            input.classList.remove('border-gray-300');
        } else {
            warning.classList.add('hidden');
            input.classList.remove('border-amber-400');
            input.classList.add('border-gray-300');
        }
    }

    // Restore old() values on validation failure
    document.addEventListener('DOMContentLoaded', () => {
        const oldCropId   = "{{ old('crop_id') }}";
        const oldVarietyId = "{{ old('crop_variety_id') }}";

        if (oldCropId) {
            document.getElementById('crop_id').value = oldCropId;
            updateVarieties(oldCropId);

            if (oldVarietyId) {
                // Wait for options to render then set
                setTimeout(() => {
                    document.getElementById('crop_variety_id').value = oldVarietyId;
                }, 0);
            }
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
                        document.getElementById('pin-feedback').textContent    = `📍 Pinned at ${e.latlng.lat.toFixed(5)}, ${e.latlng.lng.toFixed(5)}`;
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
