<x-layout>

    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<div class="w-full max-w-2xl">

    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:text-slate-400 dark:hover:text-slate-300 mb-4 inline-block font-semibold">
            ← Back to Dashboard
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Post New Harvest</h1>
    </header>

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
        <div class="mb-6 bg-[var(--color-error-bg)] border border-[var(--color-error-border)] text-[var(--color-error-text)] rounded-xl px-5 py-4 text-sm font-semibold">
            <p class="font-semibold mb-1">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm p-8">
            <form method="POST" action="{{ route('harvests.store') }}" enctype="multipart/form-data" id="harvest-form">
            @csrf

            {{-- Crop --}}
            <div class="mb-6">
                <label for="crop_search" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Crop Type <span class="text-[var(--color-error-text)]">*</span>
                </label>
                <div class="relative">
                    <input
                        type="text"
                        id="crop_search"
                        placeholder="Type to search crops..."
                        autocomplete="off"
                        class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition"
                    />
                    <div id="crop_dropdown" class="hidden absolute z-20 w-full mt-1 max-h-60 overflow-y-auto bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg"></div>
                    <select
                        name="crop_id"
                        id="crop_id"
                        class="hidden"
                        onchange="handleCropChange(this.value)"
                    >
                        <option value="" disabled {{ old('crop_id') ? '' : 'selected' }}>Select a crop</option>
                        @foreach ($crops as $crop)
                            <option value="{{ $crop->id }}" {{ old('crop_id') == $crop->id ? 'selected' : '' }}>
                                {{ $crop->name }}
                            </option>
                        @endforeach
                        <option value="other" {{ old('crop_id') === 'other' ? 'selected' : '' }}>Other (type manually)</option>
                    </select>
                </div>
                <p id="crop_error" class="hidden mt-2 text-xs text-[var(--color-error-text)]">Please select a crop.</p>
                <p id="custom_crop_error" class="hidden mt-2 text-xs text-[var(--color-error-text)]">Please enter a crop name.</p>
                <input
                    type="text"
                    name="custom_crop_name"
                    id="custom_crop_name"
                    value="{{ old('custom_crop_name') }}"
                    placeholder="Enter crop name (e.g. Dragon Fruit)"
                    class="mt-2 w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition {{ old('crop_id') === 'other' ? '' : 'hidden' }}"
                />
                @error('crop_id')
                    <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                @enderror
                @error('custom_crop_name')
                    <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                @enderror
            </div>

            {{-- Variety (cascading) --}}
            <div class="mb-6" id="variety_wrapper" style="{{ old('crop_id') ? '' : 'display:none;' }}">
                <label for="variety_search" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Variety <span class="text-[var(--color-error-text)]">*</span>
                </label>
                <div class="relative">
                    <input
                        type="text"
                        id="variety_search"
                        placeholder="Type to search varieties..."
                        autocomplete="off"
                        class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition"
                    />
                    <div id="variety_dropdown" class="hidden absolute z-20 w-full mt-1 max-h-60 overflow-y-auto bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg"></div>
                    <select
                        name="crop_variety_id"
                        id="crop_variety_id"
                        class="hidden"
                        onchange="handleVarietyChange(this.value)"
                    >
                        <option value="" disabled selected>Select a variety</option>
                    </select>
                </div>
                <input
                    type="text"
                    name="custom_variety_name"
                    id="custom_variety_name"
                    value="{{ old('custom_variety_name') }}"
                    placeholder="Enter variety name (e.g. Red Lady)"
                    class="mt-2 w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition {{ old('crop_variety_id') === 'other' ? '' : 'hidden' }}"
                />
                @error('crop_variety_id')
                    <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                @enderror
                @error('custom_variety_name')
                    <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                @enderror
                <p id="variety_error" class="hidden mt-2 text-xs text-[var(--color-error-text)]">Please select a variety.</p>
                <p id="custom_variety_error" class="hidden mt-2 text-xs text-[var(--color-error-text)]">Please enter a variety name.</p>
            </div>

            {{-- Quantity --}}
            <div class="mb-6">
                <label for="quantity_kg" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Estimated Quantity (kg) <span class="text-[var(--color-error-text)]">*</span>
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
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition"
                    oninput="validateQuantity(this)"
                />
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-medium">Estimated harvest weight. Actual weight confirmed at pickup.</p>
                <p id="quantity_warning" class="hidden mt-2 text-xs text-amber-700 dark:text-amber-400 font-bold">
                    <x-icon name="warning" class="w-4 h-4" /> That quantity seems unrealistic. Max allowed is 999,999.99 kg.
                </p>
                @error('quantity_kg')
                    <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                @enderror
                <p id="quantity_error" class="hidden mt-2 text-xs text-[var(--color-error-text)]">Please enter quantity.</p>
            </div>

            {{-- Suggested Price --}}
            <div class="mb-6">
                <label for="suggested_price_per_kg" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Your Suggested Price (₱/kg) <span class="text-slate-500 dark:text-slate-400 font-normal">(optional)</span>
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
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition"
                />
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-medium">Buyers will see this as your asking price. Leave blank if open to negotiation.</p>
                @error('suggested_price_per_kg')
                    <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                @enderror

            </div>

            {{-- Harvest Date --}}
            <div class="mb-6">
                <label for="harvest_date" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Harvest Date <span class="text-[var(--color-error-text)]">*</span>
                </label>
                <input
                    type="date"
                    name="harvest_date"
                    id="harvest_date"
                    value="{{ old('harvest_date') }}"
                    max="{{ date('Y-m-d', strtotime('+1 day')) }}"
                    min="{{ date('Y-m-d') }}"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition"
                />
                @error('harvest_date')
                    <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                @enderror
                <p id="harvest_date_error" class="hidden mt-2 text-xs text-[var(--color-error-text)]">Please select a harvest date.</p>
            </div>

            @php $lockedCoop = $coop && $coop->latitude && $coop->longitude; @endphp

            @if ($lockedCoop)
                {{-- Coop members deliver to their coop hub automatically --}}
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Delivery Destination
                    </label>
                    <div class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-200 flex items-center gap-2">
                        <x-icon name="pin" class="w-4 h-4" />
                        <span class="font-semibold">{{ $coop->company_name }} (Cooperative Hub)</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-medium">{{ $coop->office_address ?: ($coop->company_name . ' Drop-off Point') }}</p>
                    <input type="hidden" name="destination_id" value="">
                    <input type="hidden" name="destination_address" value="{{ $coop->office_address ?: ($coop->company_name . ' Drop-off Point') }}">
                    <input type="hidden" name="destination_latitude" value="{{ $coop->latitude }}">
                    <input type="hidden" name="destination_longitude" value="{{ $coop->longitude }}">
                </div>
            @else
                {{-- Destination --}}
                <div class="mb-6">
                    <label for="destination_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                        Delivery Destination <span class="text-[var(--color-error-text)]">*</span>
                    </label>
                    <select
                        name="destination_id"
                        id="destination_id"
                        class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition"
                        onchange="handleDestinationChange(this.value)"
                    >
                        <option value="" disabled {{ (!$coop && !old('destination_id')) ? 'selected' : '' }}>— Select a destination —</option>
                        @if ($coop)
                            <option
                                value="coop"
                                data-lat="{{ $coop->latitude }}"
                                data-lng="{{ $coop->longitude }}"
                                data-address="{{ $coop->office_address ?: ($coop->company_name . ' Drop-off Point') }}"
                                {{ (!old('destination_id') && old('destination_id') !== 'custom') ? 'selected' : '' }}
                            >
                                {{ $coop->company_name }} (Cooperative Hub)
                            </option>
                        @endif
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
                        <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                    @enderror
                    @error('destination_latitude')
                        <p class="mt-2 text-xs text-[var(--color-error-text)]">Please pin a destination on the map.</p>
                    @enderror
                    <p id="destination_error" class="hidden mt-2 text-xs text-[var(--color-error-text)]">Please select a delivery destination.</p>
                    <p id="destination_pin_error" class="hidden mt-2 text-xs text-[var(--color-error-text)]">Please pin a destination on the map.</p>
                </div>

                {{-- Custom Map Pin (hidden until "Custom Location" is selected) --}}
                <div id="custom_map_wrapper" class="mb-6 hidden">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Pin Your Destination</label>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Click on the map to drop a pin on your delivery destination.</p>
                    <div id="destination-map" class="w-full rounded-xl border border-slate-300 dark:border-slate-700" style="height: 300px;"></div>
                    <p id="pin-feedback" class="text-xs text-slate-500 dark:text-slate-400 mt-2 italic">No pin placed yet.</p>
                </div>
            @endif

            @php
                $advOpen = $errors->any()
                    || old('estimated_volume_cubic_m') || old('pickup_window_start') || old('pickup_window_end') || old('notes');
            @endphp
            <details class="mb-8 group" {{ $advOpen ? 'open' : '' }}>
                <summary class="flex items-center gap-1.5 text-sm font-semibold text-slate-600 dark:text-slate-300 cursor-pointer select-none hover:text-brand transition">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 transition-transform group-open:rotate-180">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                    Advanced options
                </summary>
                <div class="mt-4 space-y-6">
                    {{-- Estimated Volume --}}
                    <div>
                        <label for="estimated_volume_cubic_m" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Estimated Volume (m³) <span class="text-slate-500 dark:text-slate-400 font-normal">(optional)</span>
                        </label>
                        <input type="number" name="estimated_volume_cubic_m" id="estimated_volume_cubic_m"
                            value="{{ old('estimated_volume_cubic_m') }}" placeholder="e.g. 2.5" min="0.01" max="99999.99" step="0.01"
                            class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-medium">Used for truck space planning. Leave blank if unsure.</p>
                        @error('estimated_volume_cubic_m')
                            <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Pickup Time Window --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Pickup Window <span class="text-slate-500 dark:text-slate-400 font-normal">(optional)</span>
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="pickup_window_start" class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Earliest pickup</label>
                                <input type="time" name="pickup_window_start" id="pickup_window_start" value="{{ old('pickup_window_start') }}"
                                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition">
                            </div>
                            <div>
                                <label for="pickup_window_end" class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Latest pickup</label>
                                <input type="time" name="pickup_window_end" id="pickup_window_end" value="{{ old('pickup_window_end') }}"
                                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition">
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-medium">When can the driver pick up? Helps prioritize routes.</p>
                        @error('pickup_window_start')
                            <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                        @enderror
                        @error('pickup_window_end')
                            <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Crop Photos --}}
                    <div>
                        <label for="crop_photos" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Crop Photos <span class="text-slate-500 dark:text-slate-400 font-normal">(optional, max 5)</span>
                        </label>
                        <input
                            type="file"
                            name="crop_photos[]"
                            id="crop_photos"
                            multiple
                            accept="image/*"
                            class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-brand file:text-white dark:file:bg-gold-light dark:file:text-[#17202B] hover:file:bg-opacity-90 transition"
                        />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-medium">Upload photos of your crop to attract buyers. Max 5 images, 5MB each.</p>
                        @error('crop_photos.*')
                            <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label for="notes" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Pickup Notes <span class="text-slate-500 dark:text-slate-400 font-normal">(optional)</span>
                        </label>
                        <textarea
                            name="notes"
                            id="notes"
                            rows="4"
                            placeholder="e.g. Use the side gate, available after 8am, call before arrival"
                            class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent resize-none transition"
                        >{{ old('notes') }}</textarea>
                    </div>
                </div>
            </details>

            {{-- Hidden fields — always submitted --}}
            <input type="hidden" name="destination_address"   id="destination_address"   value="{{ old('destination_address') }}">
            <input type="hidden" name="destination_latitude"  id="destination_latitude"  value="{{ old('destination_latitude') }}">
            <input type="hidden" name="destination_longitude" id="destination_longitude" value="{{ old('destination_longitude') }}">

            {{-- Info Banner --}}
            <div class="mb-6 bg-[var(--color-success-bg)] border border-[var(--color-success-border)] rounded-xl px-4 py-3 flex gap-3 items-start">
                <span class="text-lg"><x-icon name="pin" class="w-4 h-4" /></span>
                <p class="text-sm text-[var(--color-success-text)] font-medium">
                    @if ($isIndependent)
                        Once posted, buyers can negotiate and schedule pickup with you.
                    @else
                        Once posted, your cooperative will see your harvest right away.
                    @endif
                    You can remove the post anytime from your dashboard.
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <button type="submit" id="post-harvest-btn"
                    class="flex-1 bg-brand text-white dark:bg-gold-light dark:text-[#17202B] font-bold py-3 rounded-xl hover:bg-opacity-90 transition shadow-md cursor-pointer">
                    Post Harvest
                </button>
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

    const cropSearch   = document.getElementById('crop_search');
    const cropSelect   = document.getElementById('crop_id');
    const cropDropdown = document.getElementById('crop_dropdown');
    let cropList = [];
    let cropActiveIndex = -1;

    function initCropCombobox() {
        cropList = Array.from(cropSelect.options).filter(o => o.value !== '')
            .map(o => ({ value: o.value, label: o.textContent.trim() }));
        renderCropList('');
        syncCropSearchDisplay();
    }

    function syncCropSearchDisplay() {
        const opt = cropSelect.selectedOptions[0];
        cropSearch.value = (opt && opt.value) ? opt.textContent.trim() : '';
    }

    function renderCropList(query) {
        const q = query.trim().toLowerCase();
        const matches = cropList.filter(o => o.label.toLowerCase().includes(q));
        cropDropdown.innerHTML = '';
        cropActiveIndex = -1;
        if (!matches.length) {
            const empty = document.createElement('div');
            empty.className = 'px-4 py-2.5 text-sm italic text-slate-500 dark:text-slate-400';
            empty.textContent = 'No matching crop - use Other (type manually)';
            cropDropdown.appendChild(empty);
            return;
        }
        matches.forEach(o => {
            const item = document.createElement('div');
            item.dataset.value = o.value;
            item.textContent = o.label;
            item.className = 'px-4 py-2.5 text-sm cursor-pointer text-slate-900 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800' +
                (o.value === cropSelect.value ? ' font-bold text-brand' : '');
            item.addEventListener('mousedown', e => { e.preventDefault(); selectCrop(o.value); });
            cropDropdown.appendChild(item);
        });
    }

    function selectCrop(value) {
        cropSelect.value = value;
        document.getElementById('crop_error').classList.add('hidden');
        syncCropSearchDisplay();
        closeCropDropdown();
        handleCropChange(value);
    }

    function openCropDropdown() { cropDropdown.classList.remove('hidden'); }
    function closeCropDropdown() { cropDropdown.classList.add('hidden'); }

    cropSearch.addEventListener('focus', () => { renderCropList(''); openCropDropdown(); });
    cropSearch.addEventListener('input', () => { renderCropList(cropSearch.value); openCropDropdown(); });
    cropSearch.addEventListener('blur', () => setTimeout(closeCropDropdown, 150));
    cropSearch.addEventListener('keydown', e => {
        const items = Array.from(cropDropdown.children).filter(el => el.dataset.value);
        if (!items.length) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            cropActiveIndex = e.key === 'ArrowDown'
                ? (cropActiveIndex + 1) % items.length
                : (cropActiveIndex - 1 + items.length) % items.length;
            items.forEach((el, i) => {
                el.classList.toggle('bg-slate-100', i === cropActiveIndex);
                el.classList.toggle('dark:bg-slate-800', i === cropActiveIndex);
            });
            items[cropActiveIndex].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (cropActiveIndex >= 0) selectCrop(items[cropActiveIndex].dataset.value);
        } else if (e.key === 'Escape') {
            closeCropDropdown();
        }
    });
    document.addEventListener('click', e => {
        if (e.target !== cropSearch && !cropDropdown.contains(e.target)) closeCropDropdown();
    });

    initCropCombobox();

    const varietySearch   = document.getElementById('variety_search');
    const varietySelect   = document.getElementById('crop_variety_id');
    const varietyDropdown = document.getElementById('variety_dropdown');
    let varietyList = [];
    let varietyActiveIndex = -1;

    function initVarietyCombobox() {
        varietyList = Array.from(varietySelect.options).filter(o => o.value !== '')
            .map(o => ({ value: o.value, label: o.textContent.trim() }));
        renderVarietyList('');
        syncVarietySearchDisplay();
    }

    function syncVarietySearchDisplay() {
        const opt = varietySelect.selectedOptions[0];
        varietySearch.value = (opt && opt.value) ? opt.textContent.trim() : '';
    }

    function renderVarietyList(query) {
        const q = query.trim().toLowerCase();
        const matches = varietyList.filter(o => o.label.toLowerCase().includes(q));
        varietyDropdown.innerHTML = '';
        varietyActiveIndex = -1;
        if (!matches.length) {
            const empty = document.createElement('div');
            empty.className = 'px-4 py-2.5 text-sm italic text-slate-500 dark:text-slate-400';
            empty.textContent = 'No matching variety - use Other (type manually)';
            varietyDropdown.appendChild(empty);
            return;
        }
        matches.forEach(o => {
            const item = document.createElement('div');
            item.dataset.value = o.value;
            item.textContent = o.label;
            item.className = 'px-4 py-2.5 text-sm cursor-pointer text-slate-900 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800' +
                (o.value === varietySelect.value ? ' font-bold text-brand' : '');
            item.addEventListener('mousedown', e => { e.preventDefault(); selectVariety(o.value); });
            varietyDropdown.appendChild(item);
        });
    }

    function selectVariety(value) {
        varietySelect.value = value;
        document.getElementById('variety_error').classList.add('hidden');
        syncVarietySearchDisplay();
        closeVarietyDropdown();
        handleVarietyChange(value);
    }

    function openVarietyDropdown() { varietyDropdown.classList.remove('hidden'); }
    function closeVarietyDropdown() { varietyDropdown.classList.add('hidden'); }

    varietySearch.addEventListener('focus', () => { renderVarietyList(''); openVarietyDropdown(); });
    varietySearch.addEventListener('input', () => { renderVarietyList(varietySearch.value); openVarietyDropdown(); });
    varietySearch.addEventListener('blur', () => setTimeout(closeVarietyDropdown, 150));
    varietySearch.addEventListener('keydown', e => {
        const items = Array.from(varietyDropdown.children).filter(el => el.dataset.value);
        if (!items.length) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            varietyActiveIndex = e.key === 'ArrowDown'
                ? (varietyActiveIndex + 1) % items.length
                : (varietyActiveIndex - 1 + items.length) % items.length;
            items.forEach((el, i) => {
                el.classList.toggle('bg-slate-100', i === varietyActiveIndex);
                el.classList.toggle('dark:bg-slate-800', i === varietyActiveIndex);
            });
            items[varietyActiveIndex].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (varietyActiveIndex >= 0) selectVariety(items[varietyActiveIndex].dataset.value);
        } else if (e.key === 'Escape') {
            closeVarietyDropdown();
        }
    });
    document.addEventListener('click', e => {
        if (e.target !== varietySearch && !varietyDropdown.contains(e.target)) closeVarietyDropdown();
    });

    var createHasLocation = {{ $farmerProfile && $farmerProfile->latitude ? 'true' : 'false' }};

    document.getElementById('harvest-form').addEventListener('submit', e => {
        ['crop_error','custom_crop_error','variety_error','custom_variety_error','quantity_error','harvest_date_error','destination_error','destination_pin_error']
            .forEach(id => { const el = document.getElementById(id); if (el) el.classList.add('hidden'); });

        const fail = (errId, el) => {
            e.preventDefault();
            document.getElementById(errId).classList.remove('hidden');
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.focus({ preventScroll: true });
        };

        const customCrop = document.getElementById('custom_crop_name');
        if (!cropSelect.value) return fail('crop_error', cropSearch);
        if (cropSelect.value === 'other' && !customCrop.value.trim()) return fail('custom_crop_error', customCrop);

        const varietyWrapper = document.getElementById('variety_wrapper');
        if (varietyWrapper.style.display !== 'none') {
            const varietySelect = document.getElementById('crop_variety_id');
            const customVariety = document.getElementById('custom_variety_name');
            if (!varietySelect.value) return fail('variety_error', varietySelect);
            if (varietySelect.value === 'other' && !customVariety.value.trim()) return fail('custom_variety_error', customVariety);
        }

        const qty = document.getElementById('quantity_kg');
        if (!qty.value || parseFloat(qty.value) <= 0) return fail('quantity_error', qty);

        const dateInput = document.getElementById('harvest_date');
        if (!dateInput.value) return fail('harvest_date_error', dateInput);

        const destSelect = document.getElementById('destination_id');
        if (destSelect) {
            if (!destSelect.value) return fail('destination_error', destSelect);
            if (destSelect.value === 'custom') {
                const lat = document.getElementById('destination_latitude').value;
                const lng = document.getElementById('destination_longitude').value;
                if (!lat || !lng) return fail('destination_pin_error', document.getElementById('pin-feedback'));
            }
        }

        if (createHasLocation) {
            e.preventDefault();
            swalConfirm(e.target, {
                title: 'Post Harvest?',
                text: 'Submit this harvest listing to the buyer crop board?',
                icon: 'question',
                confirmText: 'Yes, post',
                cancelText: 'Cancel',
                confirmColor: '#16283C'
            });
        }
    });

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
            initVarietyCombobox();
            handleVarietyChange('other');
        } else {
            customInput.classList.add('hidden');
            customInput.value = '';
            updateVarieties(value);
        }
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

        initVarietyCombobox();
    }

    function validateQuantity(input) {
        const warning = document.getElementById('quantity_warning');
        const value   = parseFloat(input.value);

        if (value > 999999.99 || value <= 0) {
            warning.classList.remove('hidden');
            input.classList.add('border-[var(--color-warning-text)]');
            input.classList.remove('border-slate-300', 'dark:border-slate-700');
        } else {
            warning.classList.add('hidden');
            input.classList.remove('border-[var(--color-warning-text)]');
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

        syncCropSearchDisplay();
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
@if ($lockedCoop)
document.addEventListener('DOMContentLoaded', () => {
    // Destination is locked to the coop hub; nothing to restore.
});
@else
document.addEventListener('DOMContentLoaded', () => {
    const oldDestId = "{{ old('destination_id') }}";
    if (oldDestId) {
        handleDestinationChange(oldDestId);
    } @if ($coop && !old('destination_id'))
    else {
        handleDestinationChange('coop');
    }
    @endif
});
@endif
</script>

<x-location-picker-modal />

<script>
document.addEventListener('DOMContentLoaded', function () {
    var hasLocation = {{ $farmerProfile && $farmerProfile->latitude ? 'true' : 'false' }};
    var form = document.getElementById('harvest-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        if (hasLocation) return;

        e.preventDefault();
        window.__locationPicker.open('Post', function (data) {
            var fields = {popup_latitude: data.lat, popup_longitude: data.lng, popup_address: data.address, popup_save_permanently: data.savePermanently ? '1' : '0'};
            Object.keys(fields).forEach(function (name) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = fields[name];
                form.appendChild(input);
            });
            swalConfirm(form, {
                title: 'Post Harvest?',
                text: 'Submit this harvest listing to the buyer crop board?',
                icon: 'question',
                confirmText: 'Yes, post',
                cancelText: 'Cancel',
                confirmColor: '#16283C'
            });
        });
    });
});
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('crop_photos');
    if (!input || typeof window.compressImage !== 'function') return;
    input.addEventListener('change', async function () {
      if (!input.files.length) return;
      const out = [];
      for (const f of Array.from(input.files)) out.push(await window.compressImage(f));
      const dt = new DataTransfer();
      out.forEach((f) => dt.items.add(f));
      input.files = dt.files;
    });
  });
</script>

</x-layout>
