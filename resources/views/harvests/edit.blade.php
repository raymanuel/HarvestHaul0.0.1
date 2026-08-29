<x-layout>

<div class="w-full max-w-2xl">

    <header class="pt-8 mb-8">
        <a href="{{ route('harvests.index') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:text-slate-400 dark:hover:text-slate-300 mb-4 inline-block font-semibold">
            ← Back to My Posts
        </a>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Edit Harvest</h1>
    </header>

    {{-- Status Banner --}}
    @if(in_array($harvest->status, ['completed', 'cancelled', 'negotiating', 'sold', 'assigned']))
        <div class="mb-6 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-xl px-5 py-4 flex gap-3 items-start">
            <span class="text-lg"><x-icon name="lock" class="w-4 h-4" /></span>
            <p class="text-sm text-[var(--color-warning-text)] font-medium">
                This harvest post is <strong>{{ ucfirst($harvest->status->value) }}</strong> and can no longer be edited.
            </p>
        </div>
    @elseif($harvest->status->value === 'partially_sold' && (float) ($harvest->remaining_quantity_kg ?? 0) <= 0)
        <div class="mb-6 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-xl px-5 py-4 flex gap-3 items-start">
            <span class="text-lg"><x-icon name="lock" class="w-4 h-4" /></span>
            <p class="text-sm text-[var(--color-warning-text)] font-medium">
                This harvest is fully sold and can no longer be edited.
            </p>
        </div>
    @endif

    @if ($harvest->poolingJobs()->where('pooling_jobs.status', 'in', ['pending', 'confirmed', 'in_progress'])->exists())
        <div class="mb-6 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-xl px-5 py-4">
            <p class="text-sm text-[var(--color-warning-text)] font-medium">
                This harvest has an active logistics proposal. Editing is locked until the proposal is resolved.
            </p>
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

    <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm p-8">
        <form method="POST" action="{{ route('harvests.update', $harvest->id) }}" id="harvest-form">
            @csrf
            @method('PUT')

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
                        class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#16283C] focus:border-transparent transition"
                    />
                    <div id="crop_dropdown" class="hidden absolute z-20 w-full mt-1 max-h-60 overflow-y-auto bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg"></div>
                    <select
                        name="crop_id"
                        id="crop_id"
                        class="hidden"
                        onchange="handleCropChange(this.value)"
                    >
                        <option value="" disabled>Select a crop</option>
                        @foreach ($crops as $crop)
                            <option value="{{ $crop->id }}" {{ old('crop_id', $harvest->crop_id) == $crop->id ? 'selected' : '' }}>
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
                    class="mt-2 w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#16283C] focus:border-transparent transition {{ old('crop_id') === 'other' ? '' : 'hidden' }}"
                />
                @error('crop_id')
                    <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                @enderror
                @error('custom_crop_name')
                    <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                @enderror
            </div>

            {{-- Variety (cascading) --}}
            <div class="mb-6" id="variety_wrapper">
                <label for="variety_search" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Variety <span class="text-[var(--color-error-text)]">*</span>
                </label>
                <div class="relative">
                    <input
                        type="text"
                        id="variety_search"
                        placeholder="Type to search varieties..."
                        autocomplete="off"
                        class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#16283C] focus:border-transparent transition"
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
                    class="mt-2 w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#16283C] focus:border-transparent transition {{ old('crop_variety_id') === 'other' ? '' : 'hidden' }}"
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
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Quantity (kg) <span class="text-[var(--color-error-text)]">*</span>
                </label>
                @php
                    $editQty = $harvest->status->value === 'partially_sold' && $harvest->remaining_quantity_kg
                        ? $harvest->remaining_quantity_kg
                        : $harvest->quantity_kg;
                @endphp
                <input
                    type="number"
                    name="quantity_kg"
                    id="quantity_kg"
                    value="{{ old('quantity_kg', $editQty) }}"
                    placeholder="e.g. 500"
                    min="0.01"
                    max="999999.99"
                    step="0.01"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#16283C] focus:border-transparent transition"
                    oninput="validateQuantity(this)"
                />
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-medium">Actual weight confirmed at pickup.</p>
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
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Your Suggested Price (₱/kg) <span class="text-slate-500 dark:text-slate-400 font-normal">(optional)</span>
                </label>
                <input
                    type="number"
                    name="suggested_price_per_kg"
                    id="suggested_price_per_kg"
                    value="{{ old('suggested_price_per_kg', $harvest->suggested_price_per_kg) }}"
                    placeholder="e.g. 45.00"
                    min="0"
                    max="99999.99"
                    step="0.01"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#16283C] focus:border-transparent transition"
                />
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 font-medium">Buyers will see this as your asking price. Leave blank if open to negotiation.</p>
                @error('suggested_price_per_kg')
                    <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                @enderror
                <div id="market-reference" class="hidden mt-3 bg-[#16283C]/5 dark:bg-[#16283C]/10 border border-[#16283C]/15 dark:border-[#16283C]/20 rounded-xl px-4 py-3">
                    <p class="text-[11px] font-bold text-[#16283C] dark:text-[#D7BC7A] uppercase tracking-wider mb-1">Market Reference (DA RFO12)</p>
                    <p id="market-reference-text" class="text-xs text-slate-600 dark:text-slate-400"></p>
                </div>
            </div>

            {{-- Harvest Date --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Harvest Date <span class="text-[var(--color-error-text)]">*</span>
                </label>
                <input
                    type="date"
                    name="harvest_date"
                    value="{{ old('harvest_date', $harvest->harvest_date?->format('Y-m-d')) }}"
                    max="{{ date('Y-m-d', strtotime('+1 day')) }}"
                    min="{{ date('Y-m-d') }}"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#16283C] focus:border-transparent transition"
                />
                @error('harvest_date')
                    <p class="mt-2 text-xs text-[var(--color-error-text)]">{{ $message }}</p>
                @enderror
                <p id="harvest_date_error" class="hidden mt-2 text-xs text-[var(--color-error-text)]">Please select a harvest date.</p>
            </div>

            {{-- Notes --}}
            <div class="mb-8">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Pickup Notes <span class="text-slate-500 dark:text-slate-400 font-normal">(optional)</span>
                </label>
                <textarea
                    name="notes"
                    rows="4"
                    placeholder="e.g. Use the side gate, available after 8am, call before arrival"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#16283C] focus:border-transparent resize-none transition"
                >{{ old('notes', $harvest->notes) }}</textarea>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <button type="submit"
                    class="flex-1 bg-[#16283C] text-white font-bold py-3 rounded-xl hover:bg-opacity-90 transition shadow-md cursor-pointer">
                    Save Changes
                </button>
                <a href="{{ route('harvests.index') }}"
                    class="flex-1 text-center bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 font-bold py-3 rounded-xl transition">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

<script>
    const cropVarieties = @json(
        $crops->mapWithKeys(fn($crop) => [
            $crop->id => $crop->varieties->map(fn($v) => ['id' => $v->id, 'name' => $v->name])
        ])
    );

    const cropNames = @json($crops->mapWithKeys(fn($crop) => [$crop->id => $crop->name]));

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
                (o.value === cropSelect.value ? ' font-bold text-[#16283C]' : '');
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
                (o.value === varietySelect.value ? ' font-bold text-[#16283C]' : '');
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

    document.getElementById('harvest-form').addEventListener('submit', e => {
        ['crop_error','custom_crop_error','variety_error','custom_variety_error','quantity_error','harvest_date_error']
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

        const dateInput = document.querySelector('input[name="harvest_date"]');
        if (!dateInput.value) return fail('harvest_date_error', dateInput);
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

        select.innerHTML = '<option value="" disabled>Select a variety</option>';

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

    document.addEventListener('DOMContentLoaded', () => {
        const currentCropId   = "{{ old('crop_id', $harvest->crop_id) }}";
        const currentVarietyId = "{{ old('crop_variety_id', $harvest->crop_variety_id) }}";

        if (currentCropId === 'other') {
            document.getElementById('crop_id').value = 'other';
            handleCropChange('other');
            document.getElementById('custom_crop_name').value = "{{ old('custom_crop_name') }}";
            if (currentVarietyId === 'other') {
                document.getElementById('custom_variety_name').value = "{{ old('custom_variety_name') }}";
            }
        } else if (currentCropId) {
            updateVarieties(currentCropId, currentVarietyId);
            fetchMarketPrice(cropNames[currentCropId] || '');
        }

        syncCropSearchDisplay();
    });
</script>
</x-layout>
