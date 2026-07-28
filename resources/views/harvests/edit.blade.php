<x-layout>

<div class="w-full max-w-2xl">

    <header class="pt-8 mb-8">
        <a href="{{ route('harvests.index') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:text-slate-400 dark:hover:text-slate-300 mb-4 inline-block font-semibold">
            ← Back to My Posts
        </a>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Edit Harvest</h1>
        <p class="text-slate-500 dark:text-slate-400 font-medium">Update your crop details for <strong>{{ $harvest->crop_type }}</strong>.</p>
    </header>

    {{-- Status Banner --}}
    @if(in_array($harvest->status, ['completed', 'cancelled', 'negotiating', 'sold', 'assigned']))
        <div class="mb-6 bg-amber-50 border border-amber-200 dark:bg-amber-950/20 dark:border-amber-500/20 rounded-xl px-5 py-4 flex gap-3 items-start">
            <span class="text-lg"><x-icon name="lock" class="w-4 h-4" /></span>
            <p class="text-sm text-amber-700 dark:text-amber-400 font-medium">
                This harvest post is <strong>{{ ucfirst($harvest->status) }}</strong> and can no longer be edited.
            </p>
        </div>
    @elseif($harvest->status === 'partially_sold' && (float) ($harvest->remaining_quantity_kg ?? 0) <= 0)
        <div class="mb-6 bg-amber-50 border border-amber-200 dark:bg-amber-950/20 dark:border-amber-500/20 rounded-xl px-5 py-4 flex gap-3 items-start">
            <span class="text-lg"><x-icon name="lock" class="w-4 h-4" /></span>
            <p class="text-sm text-amber-700 dark:text-amber-400 font-medium">
                This harvest is fully sold and can no longer be edited.
            </p>
        </div>
    @endif

    @if ($harvest->poolingJobs()->where('pooling_jobs.status', 'in', ['pending', 'confirmed', 'in_progress'])->exists())
        <div class="mb-6 bg-amber-50 border border-amber-200 dark:bg-amber-950/20 dark:border-amber-500/20 rounded-xl px-5 py-4">
            <p class="text-sm text-amber-700 dark:text-amber-400 font-medium">
                This harvest has an active logistics proposal. Editing is locked until the proposal is resolved.
            </p>
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
        <form method="POST" action="{{ route('harvests.update', $harvest->id) }}">
            @csrf
            @method('PUT')

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
                    <option value="" disabled>Select a crop</option>
                    @foreach ($crops as $crop)
                        <option value="{{ $crop->id }}" {{ old('crop_id', $harvest->crop_id) == $crop->id ? 'selected' : '' }}>
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
            <div class="mb-6" id="variety_wrapper">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Variety <span class="text-red-400">*</span>
                </label>
                <select
                    name="crop_variety_id"
                    id="crop_variety_id"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition"
                    onchange="handleVarietyChange(this.value)"
                    required
                >
                    <option value="" disabled>Select a variety</option>
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
                    Quantity (kg) <span class="text-red-400">*</span>
                </label>
                @php
                    $editQty = $harvest->status === 'partially_sold' && $harvest->remaining_quantity_kg
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
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition"
                    oninput="validateQuantity(this)"
                    required
                />
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500 font-medium">Actual weight confirmed at pickup.</p>
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
                    name="suggested_price_per_kg"
                    id="suggested_price_per_kg"
                    value="{{ old('suggested_price_per_kg', $harvest->suggested_price_per_kg) }}"
                    placeholder="e.g. 45.00"
                    min="0"
                    max="99999.99"
                    step="0.01"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition"
                />
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500 font-medium">Buyers will see this as your asking price. Leave blank if open to negotiation.</p>
                @error('suggested_price_per_kg')
                    <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Harvest Date --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                    Harvest Date <span class="text-slate-400 dark:text-slate-500 font-normal">(optional)</span>
                </label>
                <input
                    type="date"
                    name="harvest_date"
                    value="{{ old('harvest_date', $harvest->harvest_date?->format('Y-m-d')) }}"
                    max="{{ date('Y-m-d', strtotime('+1 day')) }}"
                    min="{{ date('Y-m-d') }}"
                    class="w-full border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-3 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#2D8A37] focus:border-transparent transition"
                />
                @error('harvest_date')
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
                >{{ old('notes', $harvest->notes) }}</textarea>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                <button type="submit"
                    class="flex-1 bg-[#2D8A37] text-white font-bold py-3 rounded-xl hover:bg-opacity-90 transition shadow-md cursor-pointer">
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
        }
    });
</script>
</x-layout>
