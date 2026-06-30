<x-register-layout maxWidth="480px">

    <div class="mb-8 text-center">
        <div class="w-14 h-14 bg-gradient-to-tr from-[#1E3A8A] to-[#3B82F6] text-white rounded-2xl flex items-center justify-center mx-auto mb-3.5 shadow-md shadow-[#1E3A8A]/10">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
        </div>
        <h2 class="text-xl font-extrabold text-slate-800 heading-font tracking-tight">Commercial Buyer</h2>
        <p class="text-xs text-slate-505 mt-1.5 font-semibold">Join the network, purchase harvests directly, and manage drops</p>
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
        <input type="hidden" name="role" value="buyer">

        {{-- REPRESENTATIVE NAME --}}
        <div class="form-group">
            <div class="relative">
                <input type="text" name="name" placeholder="Representative Name" required value="{{ old('name') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-[#1E3A8A]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]/10 focus:border-[#1E3A8A] transition">
            </div>
        </div>

        {{-- EMAIL --}}
        <div class="form-group">
            <div class="relative">
                <input type="email" name="email" placeholder="Business Email Address" required value="{{ old('email') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-[#1E3A8A]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]/10 focus:border-[#1E3A8A] transition">
            </div>
        </div>

        {{-- PHONE --}}
        <div class="form-group">
            <div class="relative">
                <input type="text" name="phone" placeholder="Contact Number" required value="{{ old('phone') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-[#1E3A8A]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]/10 focus:border-[#1E3A8A] transition">
            </div>
        </div>

        {{-- COMPANY NAME --}}
        <div class="form-group">
            <div class="relative">
                <input type="text" name="company_name" placeholder="Company Name / Business Name" value="{{ old('company_name') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-[#1E3A8A]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]/10 focus:border-[#1E3A8A] transition">
            </div>
        </div>

        {{-- AFFILIATION --}}
        <div class="form-group space-y-2">
            <label class="text-xs font-bold text-slate-655 block">
                Are you a member of a cooperative? <span class="text-red-505">*</span>
            </label>

            <div class="grid grid-cols-2 gap-3.5">
                <!-- Independent Card -->
                <label id="label-independent" class="flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#1E3A8A]/30 hover:bg-[#1E3A8A]/10">
                    <input type="radio" name="affiliation_type" value="independent"
                        {{ old('affiliation_type', 'independent') === 'independent' ? 'checked' : '' }}
                        class="hidden" onchange="handleAffiliation()">
                    <span class="text-xs font-bold text-slate-800">Independent</span>
                    <span class="text-[9px] text-slate-400 font-medium leading-tight">No cooperative</span>
                </label>

                <!-- Cooperative Card -->
                <label id="label-cooperative" class="flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#1E3A8A]/30 hover:bg-[#1E3A8A]/10">
                    <input type="radio" name="affiliation_type" value="cooperative"
                        {{ old('affiliation_type') === 'cooperative' ? 'checked' : '' }}
                        class="hidden" onchange="handleAffiliation()">
                    <span class="text-xs font-bold text-slate-800">Cooperative</span>
                    <span class="text-[9px] text-slate-400 font-medium leading-tight">Under a cooperative</span>
                </label>
            </div>
        </div>

        {{-- Cooperative dropdown — only shown if under a coop --}}
        <div id="coop-field" style="display:none;" class="form-group space-y-1.5">
            <label class="text-xs font-bold text-slate-650 block">
                Select Your Cooperative <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <div class="relative">
                <select name="cooperative_id" id="cooperative_id"
                    class="pl-4 pr-10 py-3 w-full bg-white/80 border border-[#1E3A8A]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]/10 focus:border-[#1E3A8A] transition appearance-none cursor-pointer text-sm text-slate-700">
                    <option value="">— Select your cooperative —</option>
                    @foreach($cooperatives as $coop)
                        <option value="{{ $coop->id }}"
                            {{ old('cooperative_id') == $coop->id ? 'selected' : '' }}>
                            {{ $coop->company_name }}
                        </option>
                    @endforeach
                </select>
                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </span>
            </div>
            @error('cooperative_id')
                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
            @enderror
        </div>

        {{-- PASSWORD --}}
        <div class="form-group">
            <div class="relative">
                <input type="password" name="password" id="password" placeholder="Password" required
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-[#1E3A8A]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]/10 focus:border-[#1E3A8A] transition">
                <button type="button" onclick="togglePassword('password', 'eyeIcon1')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#1E3A8A] transition focus:outline-none">
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
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-[#1E3A8A]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1E3A8A]/10 focus:border-[#1E3A8A] transition">
                <button type="button" onclick="togglePassword('password_confirmation', 'eyeIcon2')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#1E3A8A] transition focus:outline-none">
                    <svg id="eyeIcon2" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full py-3 bg-gradient-to-r from-[#1E3A8A] to-[#3B82F6] hover:brightness-105 text-white font-bold rounded-xl text-sm shadow-md shadow-[#1E3A8A]/10 hover:shadow-lg transition duration-200 transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
                Register as Commercial Buyer
            </button>
        </div>

        <div class="mt-6 pt-5 border-t border-slate-100/80 text-center text-xs font-semibold text-slate-455">
            Not a commercial buyer?
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
    </form>

    @push('scripts')
    <script>
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

        function handleAffiliation() {
            const independent = document.querySelector('input[name="affiliation_type"][value="independent"]');
            const cooperative = document.querySelector('input[name="affiliation_type"][value="cooperative"]');
            const coopField   = document.getElementById('coop-field');
            const labelInd    = document.getElementById('label-independent');
            const labelCoop   = document.getElementById('label-cooperative');

            if (independent.checked) {
                labelInd.className = "flex flex-col items-center justify-center p-4 border-2 border-[#1E3A8A] bg-[#1E3A8A]/10 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 scale-[1.02] shadow-sm";
            } else {
                labelInd.className = "flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#1E3A8A]/30 hover:bg-[#1E3A8A]/10";
            }

            if (cooperative.checked) {
                labelCoop.className = "flex flex-col items-center justify-center p-4 border-2 border-[#1E3A8A] bg-[#1E3A8A]/10 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 scale-[1.02] shadow-sm";
            } else {
                labelCoop.className = "flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#1E3A8A]/30 hover:bg-[#1E3A8A]/10";
            }

            coopField.style.display = cooperative.checked ? 'block' : 'none';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const oldAffiliation = "{{ old('affiliation_type') }}";
            if (oldAffiliation) {
                const radio = document.querySelector(`input[name="affiliation_type"][value="${oldAffiliation}"]`);
                if (radio) { radio.checked = true; }
            }
            handleAffiliation();
        });
    </script>
    <style>
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear { display:none !important; }
    </style>
    @endpush
</x-register-layout>
