<x-layout title="Register Fleet Vehicle">

    <div class="w-full max-w-2xl mx-auto pb-12">

        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <a href="{{ route('logistics.vehicles.index') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mb-4 inline-block font-semibold transition">
                ← Back to Fleet Vehicles
            </a>
            <span class="text-xs font-bold uppercase tracking-wider text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/20 px-3 py-1.5 rounded-lg border border-sky-500/10 dark:border-sky-500/20 inline-block mb-2">Fleet Integration</span>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Add Fleet Vehicle</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium">Add a truck, van, or transporter to deploy optimized cargo consolidate routes.</p>
        </header>

        {{-- Add Form --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 mb-6 heading-font">Vehicle Configuration Details</h2>

            <form method="POST" action="{{ route('logistics.vehicles.store') }}">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                    {{-- Truck Name --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Vehicle Name / Label <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="truck_name" value="{{ old('truck_name') }}" required placeholder="e.g. Matutum Transporter A"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:focus:ring-emerald-400/30 focus:border-emerald-500 dark:focus:border-emerald-400 transition">
                        @error('truck_name')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Plate Number --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Plate Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="plate_number" value="{{ old('plate_number') }}" required placeholder="e.g. ABC 1234"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:focus:ring-emerald-400/30 focus:border-emerald-500 dark:focus:border-emerald-400 transition font-mono uppercase">
                        @error('plate_number')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                    {{-- Vehicle Type --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Vehicle Type <span class="text-red-500">*</span>
                        </label>
                        <select name="vehicle_type" required
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:focus:ring-emerald-400/30 focus:border-emerald-500 dark:focus:border-emerald-400 transition">
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
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Weight Capacity (kg) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="capacity_kg" value="{{ old('capacity_kg') }}" required min="0" placeholder="e.g. 5000"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:focus:ring-emerald-400/30 focus:border-emerald-500 dark:focus:border-emerald-400 transition">
                        @error('capacity_kg')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                    {{-- Assigned Driver --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Assigned Driver
                        </label>
                        <select name="driver_id"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:focus:ring-emerald-400/30 focus:border-emerald-500 dark:focus:border-emerald-400 transition">
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
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Initial Fleet Status <span class="text-red-500">*</span>
                        </label>
                        <select name="status" required
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:focus:ring-emerald-400/30 focus:border-emerald-500 dark:focus:border-emerald-400 transition">
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
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                        Operational Notes
                    </label>
                    <textarea name="notes" placeholder="Optional notes regarding vehicle height limits, refrigeration status, or driver specifications..." rows="4"
                        class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:focus:ring-emerald-400/30 focus:border-emerald-500 dark:focus:border-emerald-400 transition"></textarea>
                    @error('notes')
                        <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="bg-gradient-to-tr from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white text-sm font-bold px-6 py-3.5 rounded-xl border border-emerald-600/20 dark:border-emerald-400/25 shadow-md shadow-emerald-600/15 dark:shadow-emerald-900/30 hover:shadow-lg hover:translate-y-[-1px] active:translate-y-0 transition-all duration-200"
                        style="background-color: #059669; text-shadow: 0 1px 2px rgba(0,0,0,0.15);">
                        Add Vehicle
                    </button>
                    <a href="{{ route('logistics.vehicles.index') }}" 
                        class="text-xs font-bold text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-350 px-4 py-3.5 rounded-xl border border-slate-200/60 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900/30 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

    </div>

</x-layout>
