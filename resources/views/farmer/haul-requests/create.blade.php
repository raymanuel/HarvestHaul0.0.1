<x-layout title="Request Pickup — HarvestHaul">
    <x-page-header
        variant="back-link"
        title="Request a Pickup"
        :backHref="route('farmer.haul-requests.index')"
        backLabel="← Back to Pickup Requests"
    />

    @if(! $cooperative)
        <x-card>
            <x-empty-state
                type="first-use"
                title="Join a cooperative first"
                description="Pickup requests are handled by your cooperative. Wait for your membership to be approved, then come back to file a request."
            />
        </x-card>
    @else
        <div class="max-w-3xl">
            <x-card>
                <x-section-label title="Harvest Details" />
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">
                    Tell your cooperative what you have ready for pickup. Your cooperative confirms the final
                    weight, quality grade, and buying price when the crop is received.
                </p>

                <form method="POST" action="{{ route('farmer.haul-requests.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <x-select
                                name="crop_id"
                                label="Crop"
                                :options="$crops->pluck('name', 'id')"
                                placeholder="Select your crop"
                                :required="true"
                                :error="$errors->first('crop_id')"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label for="crop_variety_id" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Variety (optional)</label>
                            <select name="crop_variety_id" id="crop_variety_id"
                                class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-700/25 focus:border-brand-700 bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white transition">
                                <option value="">Select a variety...</option>
                                @foreach($varieties as $variety)
                                    <option value="{{ $variety->id }}" data-crop="{{ $variety->crop_id }}" {{ old('crop_variety_id') == $variety->id ? 'selected' : '' }}>
                                        {{ $variety->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('crop_variety_id')
                                <p class="text-xs text-[var(--color-error-text)] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-select
                            name="packaging_type_id"
                            label="Packaging"
                            :options="$packagings->pluck('name', 'id')"
                            placeholder="Sack, crate, other"
                            :error="$errors->first('packaging_type_id')"
                        />

                        <x-input
                            name="estimated_sacks"
                            label="Estimated Sacks or Packages"
                            type="number"
                            min="1"
                            step="1"
                            placeholder="e.g. 80"
                            :error="$errors->first('estimated_sacks')"
                        />

                        <x-input
                            name="estimated_weight_kg"
                            label="Estimated Weight (kg)"
                            type="number"
                            min="0.01"
                            step="0.01"
                            placeholder="e.g. 4000"
                            :required="true"
                            :error="$errors->first('estimated_weight_kg')"
                        />

                        <x-input
                            name="harvest_date"
                            label="Harvest Date"
                            type="date"
                            :error="$errors->first('harvest_date')"
                        />
                    </div>

                    <x-section-label title="Pickup Schedule" class="pt-2" />
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <x-input
                                name="preferred_pickup_date"
                                label="Preferred Pickup Date"
                                type="date"
                                :min="now()->toDateString()"
                                :required="true"
                                :error="$errors->first('preferred_pickup_date')"
                            />
                        </div>

                        <x-input
                            name="pickup_window_start"
                            label="Pickup Window Start"
                            type="time"
                            :required="true"
                            :error="$errors->first('pickup_window_start')"
                        />

                        <x-input
                            name="pickup_window_end"
                            label="Pickup Window End"
                            type="time"
                            :required="true"
                            :error="$errors->first('pickup_window_end')"
                        />

                        <p class="text-xs text-slate-500 dark:text-slate-400 sm:col-span-2 -mt-2">
                            Choose the time range your farmer is available at the pickup location. The cooperative plans
                            the trip so the truck arrives inside this window.
                        </p>
                    </div>

                    <x-section-label title="Where to Pick Up" class="pt-2" />
                    <div class="space-y-5">
                        <x-input
                            name="pickup_location"
                            label="Pickup Location"
                            placeholder="Farm / barangay address where the truck will go"
                            :required="true"
                            :error="$errors->first('pickup_location')"
                        />

                        <x-location-picker
                            latField="pickup_location_lat"
                            lngField="pickup_location_lng"
                            label="Pin Your Farm on the Map"
                        />

                        <div>
                            <label for="notes" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Notes for your cooperative (optional)</label>
                            <textarea name="notes" id="notes" rows="3" placeholder="Road conditions, gate instructions, any other information"
                                class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-700/25 focus:border-brand-700 bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white transition">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="text-xs text-[var(--color-error-text)] mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100 dark:border-slate-700/60">
                        <x-button tag="a" variant="ghost" href="{{ route('farmer.haul-requests.index') }}">Cancel</x-button>
                        <x-button variant="primary">Send Pickup Request</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var cropSelect = document.querySelector('[name="crop_id"]');
                var varietySelect = document.getElementById('crop_variety_id');
                if (!cropSelect || !varietySelect) return;

                function filterVarieties() {
                    var selected = cropSelect.value;
                    var options = varietySelect.querySelectorAll('option');
                    options.forEach(function (option) {
                        if (!option.value) { option.hidden = false; return; }
                        option.hidden = option.getAttribute('data-crop') !== selected;
                    });
                }

                cropSelect.addEventListener('change', filterVarieties);
                filterVarieties();
            });
        </script>
    @endpush
</x-layout>