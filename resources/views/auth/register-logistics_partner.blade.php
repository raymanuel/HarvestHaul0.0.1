<x-register-layout maxWidth="480px">

    <div class="mb-8 text-center">
<<<<<<< HEAD
        <div class="w-14 h-14 bg-gradient-to-tr from-[#2D6A2F] to-[#5A8A3C] text-white rounded-2xl flex items-center justify-center mx-auto mb-3.5 shadow-md shadow-[#2D6A2F]/10">
=======
        <div class="w-14 h-14 bg-gradient-to-tr from-blue-600 to-indigo-500 text-white rounded-2xl flex items-center justify-center mx-auto mb-3.5 shadow-md shadow-blue-500/10">
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1m-6 0a1 1 0 001-1m9 1a1 1 0 01-1-1m-3 0a1 1 0 001-1m-1 0H8m9-1v-4a1 1 0 00-1-1h-2" />
            </svg>
        </div>
        <h2 class="text-xl font-extrabold text-slate-800 heading-font tracking-tight">Logistics Partner</h2>
<<<<<<< HEAD
        <p class="text-xs text-slate-505 mt-1.5 font-semibold">Join the network, dispatch trucks, and secure cargo contracts</p>
=======
        <p class="text-xs text-slate-500 mt-1.5 font-semibold">Join the network, dispatch trucks, and secure cargo contracts</p>
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border border-red-200/50 dark:border-red-900/30 rounded-xl">
            <div class="flex items-start gap-2.5">
                <span class="text-red-500 mt-0.5 text-xs">⚠️</span>
                <ul class="text-xs text-red-600 dark:text-red-400 list-disc list-inside space-y-1 text-left">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('register.store') }}" method="POST" class="space-y-4">
        @csrf
        <input type="hidden" name="role" value="logistics_partner">

        {{-- REPRESENTATIVE NAME --}}
        <div class="form-group">
            <div class="relative">
                <input type="text" name="name" placeholder="Full Name / Representative Name" required value="{{ old('name') }}"
<<<<<<< HEAD
                    class="px-4 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition">
=======
                    class="px-4 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            </div>
        </div>

        {{-- EMAIL --}}
        <div class="form-group">
            <div class="relative">
                <input type="email" name="email" placeholder="Company Email Address" required value="{{ old('email') }}"
<<<<<<< HEAD
                    class="px-4 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition">
=======
                    class="px-4 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            </div>
        </div>

        {{-- PHONE --}}
        <div class="form-group">
            <div class="relative">
                <input type="text" name="phone" placeholder="Contact Number" required value="{{ old('phone') }}"
<<<<<<< HEAD
                    class="px-4 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition">
=======
                    class="px-4 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            </div>
        </div>

        {{-- COMPANY NAME --}}
        <div class="form-group">
            <div class="relative">
                <input type="text" name="company_name" placeholder="Registered Company / Cooperative Name" required value="{{ old('company_name') }}"
<<<<<<< HEAD
                    class="px-4 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition">
=======
                    class="px-4 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            </div>
        </div>

        {{-- BUSINESS PERMIT NO --}}
        <div class="form-group">
            <div class="relative">
<<<<<<< HEAD
                <input type="text" name="business_permit_no" placeholder="Business Permit No. (Optional)" value="{{ old('business_permit_no') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition">
=======
                <input type="text" name="business_permit_no" placeholder="Business Permit No." required value="{{ old('business_permit_no') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            </div>
            @error('business_permit_no')
                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
            @enderror
        </div>

        {{-- Logistics Type Selector --}}
        <div class="form-group space-y-2">
<<<<<<< HEAD
            <label class="text-xs font-bold text-slate-650 block">
                What type of organization are you? <span class="text-red-505">*</span>
=======
            <label class="text-xs font-bold text-slate-600 block">
                What type of organization are you? <span class="text-red-500">*</span>
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            </label>
            @error('logistics_type')
                <p class="text-xs text-red-500 mt-0.5 font-semibold">{{ $message }}</p>
            @enderror
            <div class="grid grid-cols-2 gap-3.5">
                <!-- Company Card -->
<<<<<<< HEAD
                <label id="label-company" class="flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#2D6A2F]/30 hover:bg-[#EFF2E9]/10">
=======
                <label id="label-company" class="flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-blue-300 hover:bg-blue-50/10">
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
                    <input type="radio" name="logistics_type" value="company"
                        {{ old('logistics_type') === 'company' ? 'checked' : '' }}
                        class="hidden" onchange="handleLogisticsType()">
                    <span class="text-xs font-bold text-slate-800">Logistics Company</span>
                    <span class="text-[9px] text-slate-400 font-medium leading-tight">Commercial hauler</span>
                </label>

                <!-- Cooperative Card -->
<<<<<<< HEAD
                <label id="label-cooperative" class="flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#2D6A2F]/30 hover:bg-[#EFF2E9]/10">
=======
                <label id="label-cooperative" class="flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-emerald-300 hover:bg-emerald-50/10">
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
                    <input type="radio" name="logistics_type" value="cooperative"
                        {{ old('logistics_type') === 'cooperative' ? 'checked' : '' }}
                        class="hidden" onchange="handleLogisticsType()">
                    <span class="text-xs font-bold text-slate-800">Cooperative</span>
                    <span class="text-[9px] text-slate-400 font-medium leading-tight">Agribusiness entity</span>
                </label>
            </div>
        </div>

        {{-- CDA Registration No — only shown for cooperatives --}}
        <div id="cda-field" style="display:none;" class="form-group">
            <div class="relative">
<<<<<<< HEAD
                <input type="text" name="cda_registration_no" placeholder="CDA Registration No. (Optional)" value="{{ old('cda_registration_no') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition">
=======
                <input type="text" name="cda_registration_no" placeholder="CDA Registration No. (Required for Cooperatives)" value="{{ old('cda_registration_no') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition">
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
            </div>
            @error('cda_registration_no')
                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
            @enderror
        </div>

        {{-- PASSWORD --}}
        <div class="form-group">
            <div class="relative">
                <input type="password" name="password" id="password" placeholder="Password" required
<<<<<<< HEAD
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition">
                <button type="button" onclick="togglePassword('password', 'eyeIcon1')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#2D6A2F] transition focus:outline-none">
=======
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                <button type="button" onclick="togglePassword('password', 'eyeIcon1')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition focus:outline-none">
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
                    <svg id="eyeIcon1" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
            @enderror
        </div>

        {{-- CONFIRM PASSWORD --}}
        <div class="form-group">
            <div class="relative">
                <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm Password" required
<<<<<<< HEAD
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-[#2D6A2F]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#2D6A2F]/10 focus:border-[#2D6A2F] transition">
                <button type="button" onclick="togglePassword('password_confirmation', 'eyeIcon2')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#2D6A2F] transition focus:outline-none">
=======
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-slate-200/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                <button type="button" onclick="togglePassword('password_confirmation', 'eyeIcon2')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition focus:outline-none">
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
                    <svg id="eyeIcon2" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="pt-2">
<<<<<<< HEAD
            <button type="submit" class="w-full py-3 bg-gradient-to-r from-[#2D6A2F] to-[#5A8A3C] hover:brightness-105 text-white font-bold rounded-xl text-sm shadow-md shadow-[#2D6A2F]/10 hover:shadow-lg transition duration-200 transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
=======
            <button type="submit" class="w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-500 hover:from-blue-700 hover:to-indigo-600 text-white font-bold rounded-xl text-sm shadow-md shadow-blue-500/10 hover:shadow-lg transition duration-200 transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
                Register as Logistics Coordinator
            </button>
        </div>

<<<<<<< HEAD
        <div class="mt-6 pt-5 border-t border-slate-100/80 text-center text-xs font-semibold text-slate-455">
            Not a logistics coordinator?
            <a href="{{ route('register.role', 'farmer') }}"
                class="text-[#2D6A2F] hover:text-[#2D6A2F]/80 transition ml-1 hover:underline">
                Sign up as Farmer
            </a>
        </div>
        <div class="mt-3 text-center">
            <a href="/" class="text-slate-400 hover:text-[#2D6A2F] text-xs font-bold flex items-center justify-center gap-1">
                ← Return to Homepage
            </a>
        </div>
=======
        <div class="mt-6 pt-5 border-t border-slate-100/80 text-center text-xs font-semibold text-slate-400">
            Not a logistics coordinator?
            <a href="{{ route('register.role', 'farmer') }}"
                class="text-blue-600 hover:text-blue-700 transition ml-1 hover:underline">
                Sign up as Farmer
            </a>
        </div>
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
    </form>

    @push('scripts')
    <script>
        function handleLogisticsType() {
            const company     = document.querySelector('input[name="logistics_type"][value="company"]');
            const cooperative = document.querySelector('input[name="logistics_type"][value="cooperative"]');
            const cdaField    = document.getElementById('cda-field');
            const labelCo     = document.getElementById('label-company');
            const labelCoop   = document.getElementById('label-cooperative');

            if (company.checked) {
<<<<<<< HEAD
                labelCo.className = "flex flex-col items-center justify-center p-4 border-2 border-[#2D6A2F] bg-[#EFF2E9]/30 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 scale-[1.02] shadow-sm";
            } else {
                labelCo.className = "flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#2D6A2F]/30 hover:bg-[#EFF2E9]/10";
            }

            if (cooperative.checked) {
                labelCoop.className = "flex flex-col items-center justify-center p-4 border-2 border-[#2D6A2F] bg-[#EFF2E9]/30 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 scale-[1.02] shadow-sm";
            } else {
                labelCoop.className = "flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#2D6A2F]/30 hover:bg-[#EFF2E9]/10";
            }

            cdaField.style.display = cooperative.checked ? 'block' : 'none';
=======
                labelCo.className = "flex flex-col items-center justify-center p-4 border-2 border-blue-500 bg-blue-50/20 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 scale-[1.02] shadow-sm";
            } else {
                labelCo.className = "flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-blue-300 hover:bg-blue-50/10";
            }

            if (cooperative.checked) {
                labelCoop.className = "flex flex-col items-center justify-center p-4 border-2 border-emerald-500 bg-emerald-50/20 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 scale-[1.02] shadow-sm";
            } else {
                labelCoop.className = "flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-emerald-300 hover:bg-emerald-50/10";
            }

            cdaField.style.display = cooperative.checked ? 'block' : 'none';
            const cdaInput = cdaField.querySelector('input');
            cdaInput.required = cooperative.checked;
>>>>>>> be7d58fa19d745d3bea8e9af8673ef92cd3ef641
        }

        document.addEventListener('DOMContentLoaded', function () {
            const oldType = "{{ old('logistics_type') }}";
            if (oldType) {
                const radio = document.querySelector(`input[name="logistics_type"][value="${oldType}"]`);
                if (radio) { radio.checked = true; handleLogisticsType(); }
            }

            @if($errors->has('cda_registration_no'))
                document.getElementById('cda-field').style.display = 'block';
            @endif
        });

        function togglePassword(fieldId, iconId) {
            const field    = document.getElementById(fieldId);
            const icon     = document.getElementById(iconId);
            const isHidden = field.type === 'password';
            field.type     = isHidden ? 'text' : 'password';
            icon.innerHTML = isHidden
                ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                   <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                   <line x1="1" y1="1" x2="23" y2="23"/>`
                : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                   <circle cx="12" cy="12" r="3"/>`;
        }
    </script>
    <style>
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear { display:none !important; }
    </style>
    @endpush
</x-register-layout>
