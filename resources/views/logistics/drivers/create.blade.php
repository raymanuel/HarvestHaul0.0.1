<x-layout title="Register Driver Account">

    <div class="w-full max-w-2xl mx-auto pb-12">

        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <a href="{{ route('logistics.drivers.index') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mb-4 inline-block font-semibold transition">
                ← Back to Drivers Fleet
            </a>
            <span class="text-xs font-bold uppercase tracking-wider text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 px-3 py-1.5 rounded-lg border border-[#3A7D44]/10 dark:border-[#3A7D44]/20 inline-block mb-2">Fleet Integration</span>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Add Driver Account</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium">Create credentials for a new carrier in your fleet to enable telemetry-guided runs.</p>
        </header>

        {{-- Add Form --}}
        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-800 dark:text-slate-200 mb-6 heading-font">Driver Registration Details</h2>

            <form method="POST" action="{{ route('logistics.drivers.store') }}">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                    {{-- Name --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Driver Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Eliseo Santos"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/30 dark:focus:ring-[#3A7D44]/30 focus:border-[#3A7D44] dark:focus:border-[#3A7D44] transition">
                        @error('name')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="e.g. eliseo@driver.com"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/30 dark:focus:ring-[#3A7D44]/30 focus:border-[#3A7D44] dark:focus:border-[#3A7D44] transition">
                        @error('email')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                    {{-- Phone --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Phone Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="e.g. +639123456789"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/30 dark:focus:ring-[#3A7D44]/30 focus:border-[#3A7D44] dark:focus:border-[#3A7D44] transition">
                        @error('phone')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- License Number --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            License Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="license_number" value="{{ old('license_number') }}" required placeholder="e.g. D01-23-456789"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-850 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/30 dark:focus:ring-[#3A7D44]/30 focus:border-[#3A7D44] dark:focus:border-[#3A7D44] transition font-mono uppercase">
                        @error('license_number')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-5">
                    {{-- Vehicle Type --}}
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                        Assigned Vehicle Type
                    </label>
                    <select name="vehicle_type"
                        class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/30 dark:focus:ring-[#3A7D44]/30 focus:border-[#3A7D44] dark:focus:border-[#3A7D44] transition">
                        <option value="" selected>None / Select Later</option>
                        <option value="6-Wheeler Flatbed" {{ old('vehicle_type') === '6-Wheeler Flatbed' ? 'selected' : '' }}>6-Wheeler Flatbed</option>
                        <option value="10-Wheeler Wing Van" {{ old('vehicle_type') === '10-Wheeler Wing Van' ? 'selected' : '' }}>10-Wheeler Wing Van</option>
                        <option value="L300 Utility Van" {{ old('vehicle_type') === 'L300 Utility Van' ? 'selected' : '' }}>L300 Utility Van</option>
                        <option value="Reefer Truck (Refrigerated)" {{ old('vehicle_type') === 'Reefer Truck (Refrigerated)' ? 'selected' : '' }}>Reefer Truck (Refrigerated)</option>
                    </select>
                    @error('vehicle_type')
                        <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
                    {{-- Password --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password" required placeholder="Minimum 8 characters"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/30 dark:focus:ring-[#3A7D44]/30 focus:border-[#3A7D44] dark:focus:border-[#3A7D44] transition">
                        @error('password')
                            <p class="text-red-500 dark:text-red-400 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password Confirmation --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password_confirmation" required placeholder="Retype password"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900/60 focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/30 dark:focus:ring-[#3A7D44]/30 focus:border-[#3A7D44] dark:focus:border-[#3A7D44] transition">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="bg-gradient-to-tr from-[#3A7D44] to-[#2E6336] hover:from-[#3A7D44] hover:to-[#2E6336] text-white text-sm font-bold px-6 py-3.5 rounded-xl border border-[#3A7D44]/20 dark:border-[#3A7D44]/25 shadow-md shadow-[#3A7D44]/15 dark:shadow-[#3A7D44]/30 hover:shadow-lg hover:translate-y-[-1px] active:translate-y-0 transition-all duration-200"
                        style="background-color: #059669; text-shadow: 0 1px 2px rgba(0,0,0,0.15);">
                        Create Account
                    </button>
                    <a href="{{ route('logistics.drivers.index') }}" 
                        class="text-xs font-bold text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-350 px-4 py-3.5 rounded-xl border border-slate-200/60 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900/30 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

    </div>

</x-layout>
