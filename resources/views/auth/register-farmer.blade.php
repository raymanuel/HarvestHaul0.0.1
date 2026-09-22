<x-register-layout maxWidth="480px">

    <div class="mb-8 text-center">
        <div class="w-14 h-14 bg-brand-700 text-white rounded-2xl flex items-center justify-center mx-auto mb-3.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a4 4 0 00-4-4H4v4a4 4 0 004 4h4z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a4 4 0 014-4h4v4a4 4 0 01-4 4h-4z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14v7" />
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-800 heading-font tracking-tight">Farmer Registration</h2>
        <p class="text-xs text-slate-500 mt-1.5 font-semibold">Create your account, then request to join your cooperative once you're signed in.</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-[var(--color-error-bg)] border border-[var(--color-error-border)] rounded-xl">
            <div class="flex items-start gap-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-error-text)] mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <ul class="text-xs text-[var(--color-error-text)] list-disc list-inside space-y-1 text-left">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('register.store') }}" method="POST" class="space-y-4">
        @csrf
        <input type="hidden" name="role" value="farmer">

        <div class="form-group">
            <label class="text-xs font-bold text-slate-700 block mb-1.5">Full Name *</label>
            <input type="text" name="name" required value="{{ old('name') }}" autocomplete="name"
                class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
        </div>

        <div class="form-group">
            <label class="text-xs font-bold text-slate-700 block mb-1.5">Email Address *</label>
            <input type="email" name="email" required value="{{ old('email') }}" autocomplete="email"
                class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
        </div>

        <div class="form-group">
            <label class="text-xs font-bold text-slate-700 block mb-1.5">Contact Number (optional)</label>
            <input type="tel" inputmode="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel"
                class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
        </div>

        <div class="form-group">
            <label class="text-xs font-bold text-slate-700 block mb-1.5">Password *</label>
            <div class="relative">
                <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8"
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                <button type="button" onclick="togglePassword('password', 'eye-password')"
                    class="absolute right-1 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center text-slate-400 hover:text-[#16283C] transition focus:outline-none">
                    <svg id="eye-password" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label class="text-xs font-bold text-slate-700 block mb-1.5">Confirm Password *</label>
            <div class="relative">
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                <button type="button" onclick="togglePassword('password_confirmation', 'eye-confirm')"
                    class="absolute right-1 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center text-slate-400 hover:text-[#16283C] transition focus:outline-none">
                    <svg id="eye-confirm" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="form-group pt-1">
            <label class="flex items-start gap-3 cursor-pointer p-3 rounded-xl bg-[#EEF0EB]/40 border border-[#16283C]/10">
                <input type="checkbox" name="accepted_terms" value="1" {{ old('accepted_terms') ? 'checked' : '' }}
                    class="mt-0.5 w-4 h-4 rounded border-slate-300 text-[#16283C] focus:ring-[#16283C] cursor-pointer shrink-0">
                <span class="text-xs text-slate-500 leading-relaxed">
                    I agree to the
                    <a href="{{ route('legal.terms') }}" class="text-[#16283C] font-semibold hover:underline">Terms & Conditions</a>
                    and
                    <a href="{{ route('legal.privacy') }}" class="text-[#16283C] font-semibold hover:underline">Privacy Policy</a>.
                </span>
            </label>
            @error('accepted_terms')
                <p class="text-xs text-[var(--color-error-text)] mt-1 font-semibold">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-1">
            <x-button type="submit" size="lg" full>
                Create Farmer Account
            </x-button>
        </div>

        <div class="mt-6 pt-5 border-t border-slate-100/80 text-center text-xs font-semibold text-slate-500">
            Not a farmer?
            <a href="{{ route('register.role', 'cooperative') }}"
                class="text-[#16283C] hover:text-[#16283C]/80 transition ml-1 hover:underline">
                Sign up as a Cooperative
            </a>
        </div>
        <div class="mt-3 text-center">
            <a href="/" class="text-slate-400 hover:text-[#16283C] text-xs font-bold flex items-center justify-center gap-1">
                ← Return to Homepage
            </a>
        </div>
    </form>

    @push('scripts')
    <script>
        function togglePassword(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon  = document.getElementById(iconId);
            const isHidden = field.type === 'password';

            field.type = isHidden ? 'text' : 'password';

            icon.innerHTML = isHidden
                ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                   <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                   <line x1="1" y1="1" x2="23" y2="23"/>`
                : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                   <circle cx="12" cy="12" r="3"/>`;
        }
    </script>
    @endpush

</x-register-layout>
