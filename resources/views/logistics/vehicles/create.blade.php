<x-layout title="Register Vehicle">

    <div class="w-full max-w-2xl mx-auto pb-12">

        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <a href="{{ route('logistics.vehicles.index') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mb-4 inline-block font-semibold transition">
                ← Back to Vehicles
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Add Vehicle</h1>
        </header>

        {{-- Add Form --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 mb-6 heading-font">Vehicle Configuration Details</h2>

            <form method="POST" action="{{ route('logistics.vehicles.store') }}">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                    {{-- Truck Name --}}
                    <div>
                        <label for="truck_name" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Vehicle Name / Label <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="truck_name" id="truck_name" value="{{ old('truck_name') }}" required placeholder="e.g. Matutum Transporter A"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 dark:focus:ring-[#16283C]/30 focus:border-[#16283C] dark:focus:border-[#16283C] transition">
                        @error('truck_name')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Plate Number --}}
                    <div>
                        <label for="plate_number" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Plate Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="plate_number" id="plate_number" value="{{ old('plate_number') }}" required placeholder="e.g. ABC 1234"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 dark:focus:ring-[#16283C]/30 focus:border-[#16283C] dark:focus:border-[#16283C] transition font-mono uppercase">
                        @error('plate_number')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                    {{-- Vehicle Type --}}
                    <div>
                        <label for="vehicle_type" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Vehicle Type <span class="text-red-500">*</span>
                        </label>
                        <select name="vehicle_type" id="vehicle_type" required
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 dark:focus:ring-[#16283C]/30 focus:border-[#16283C] dark:focus:border-[#16283C] transition">
                            <option value="" disabled selected>Select vehicle type</option>
                            <option value="6-Wheeler Flatbed" {{ old('vehicle_type') === '6-Wheeler Flatbed' ? 'selected' : '' }}>6-Wheeler Flatbed</option>
                            <option value="10-Wheeler Wing Van" {{ old('vehicle_type') === '10-Wheeler Wing Van' ? 'selected' : '' }}>10-Wheeler Wing Van</option>
                            <option value="L300 Utility Van" {{ old('vehicle_type') === 'L300 Utility Van' ? 'selected' : '' }}>L300 Utility Van</option>
                            <option value="Reefer Truck (Refrigerated)" {{ old('vehicle_type') === 'Reefer Truck (Refrigerated)' ? 'selected' : '' }}>Reefer Truck (Refrigerated)</option>
                        </select>
                        @error('vehicle_type')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Capacity in KG --}}
                    <div>
                        <label for="capacity_kg" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Weight Capacity (kg) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="capacity_kg" id="capacity_kg" value="{{ old('capacity_kg') }}" required min="0" placeholder="e.g. 5000"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 dark:focus:ring-[#16283C]/30 focus:border-[#16283C] dark:focus:border-[#16283C] transition">
                        @error('capacity_kg')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                    {{-- Volume Capacity --}}
                    <div>
                        <label for="capacity_volume_cubic_m" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Volume Capacity (m³)
                        </label>
                        <input type="number" name="capacity_volume_cubic_m" id="capacity_volume_cubic_m" value="{{ old('capacity_volume_cubic_m') }}" min="0" step="0.01" placeholder="e.g. 12.5"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 dark:focus:ring-[#16283C]/30 focus:border-[#16283C] dark:focus:border-[#16283C] transition">
                        <p class="mt-1 text-[10px] text-slate-400 font-medium">Optional. Leave blank to skip volume checks.</p>
                        @error('capacity_volume_cubic_m')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Assigned Driver --}}
                    <div>
                        <label for="driver_id" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Assigned Driver
                        </label>
                        <select name="driver_id" id="driver_id"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 dark:focus:ring-[#16283C]/30 focus:border-[#16283C] dark:focus:border-[#16283C] transition">
                            <option value="">No Driver / Idle Vehicle</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->name }} ({{ $driver->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('driver_id')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Vehicle Status --}}
                    <div>
                        <label for="status" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Initial Vehicle Status <span class="text-red-500">*</span>
                        </label>
                        <select name="status" id="status" required
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 dark:focus:ring-[#16283C]/30 focus:border-[#16283C] dark:focus:border-[#16283C] transition">
                            <option value="available" {{ old('status', 'available') === 'available' ? 'selected' : '' }}>Available</option>
                            <option value="in_transit" {{ old('status') === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                            <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                        @error('status')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Notes --}}
                <div class="mb-8">
                    <label for="notes" class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                        Notes
                    </label>
                    <textarea name="notes" id="notes" placeholder="Optional notes regarding vehicle height limits, refrigeration status, or driver specifications..." rows="4"
                        class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#16283C]/30 dark:focus:ring-[#16283C]/30 focus:border-[#16283C] dark:focus:border-[#16283C] transition"></textarea>
                    @error('notes')
                        <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <x-button type="submit" size="lg" class=".5 border border-[#16283C]/20 dark:border-[#16283C]/25">
                        Add Vehicle
                    </x-button>
                    <a href="{{ route('logistics.vehicles.index') }}" 
                        class="text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-slate-600 dark:hover:text-slate-400 px-4 py-3.5 rounded-xl border border-slate-200/60 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900/30 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

    </div>

</x-layout>
