<form method="POST" action="{{ $route }}" class="mt-6 space-y-5">
    @csrf
    @if(isset($truck) && $truck?->exists)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <x-input
            name="truck_name"
            :label="'Truck name'"
            :required="true"
            :value="old('truck_name', $truck?->truck_name)"
            placeholder="e.g. Hauler One"
        />

        <x-input
            name="plate_number"
            :label="'Plate number'"
            :required="true"
            :value="old('plate_number', $truck?->plate_number)"
            placeholder="e.g. ABC-123"
        />

        <x-input
            name="vehicle_type"
            :label="'Vehicle type'"
            :required="true"
            :value="old('vehicle_type', $truck?->vehicle_type)"
            placeholder="e.g. Box truck / refrigerated / flatbed"
        />

        <div>
            <label for="status" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Fleet status <span class="text-red-500">*</span></label>
            <x-select
                name="status"
                :required="true"
                :options="['available' => 'Available', 'in_use' => 'In Use', 'maintenance' => 'Maintenance', 'inactive' => 'Inactive']"
                :value="old('status', $truck?->status ?? 'available')"
            />
        </div>

        <x-input
            name="capacity_kg"
            type="number"
            step="0.01"
            min="1"
            :label="'Capacity (kg)'"
            :required="true"
            :value="old('capacity_kg', $truck?->capacity_kg)"
            placeholder="e.g. 3000"
        />

        <x-input
            name="capacity_volume_cubic_m"
            type="number"
            step="0.01"
            min="0.01"
            :label="'Load volume (m³)'"
            :required="true"
            :value="old('capacity_volume_cubic_m', $truck?->capacity_volume_cubic_m)"
            placeholder="e.g. 12.0"
        />

        <div>
            <label for="driver_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Assigned driver</label>
            <x-select
                name="driver_id"
                placeholder="No driver assigned"
                :options="$drivers"
                :value="old('driver_id', $truck?->driver_id)"
            />
        </div>
    </div>

    <div>
        <label for="notes" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Notes</label>
        <textarea name="notes" id="notes" rows="2" maxlength="1000" placeholder="Optional maintenance or usage notes…" class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:border-slate-900 dark:focus:border-white focus:ring-0 transition">{{ old('notes', $truck?->notes) }}</textarea>
    </div>

    <div class="flex justify-end pt-2">
        <button class="px-5 py-2.5 rounded-lg text-sm font-bold text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 transition">
            {{ isset($truck) && $truck?->exists ? 'Save changes' : 'Add to fleet' }}
        </button>
    </div>
</form>
