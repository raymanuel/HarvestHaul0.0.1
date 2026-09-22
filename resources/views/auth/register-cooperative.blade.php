<x-register-layout maxWidth="640px">

    <div class="mb-8 text-center">
        <div class="w-14 h-14 bg-brand-700 text-white rounded-2xl flex items-center justify-center mx-auto mb-3.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M3 7v14h18V7l-5-4-4 4-4-4-5 4z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 21v-8h8v8" />
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-800 heading-font tracking-tight">Cooperative Registration</h2>
        <p class="text-xs text-slate-500 mt-1.5 font-semibold">Tell us about your cooperative. Our Super Admin verifies your application before operations begin.</p>
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

    <form action="{{ route('register.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <input type="hidden" name="role" value="cooperative">

        {{-- SECTION 1: COOPERATIVE INFORMATION --}}
        <div class="rounded-2xl border border-[#16283C]/10 bg-[#EEF0EB]/30 p-5 space-y-4">
            <p class="text-xs font-bold text-[#16283C] uppercase tracking-widest">1 · Cooperative Information</p>

            <div class="form-group">
                <label class="text-xs font-bold text-slate-700 block mb-1.5">Cooperative Name *</label>
                <input type="text" name="name" placeholder="e.g. Davao Farmers Agricultural Cooperative" required value="{{ old('name') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
            </div>

            <div class="form-group">
                <label class="text-xs font-bold text-slate-700 block mb-1.5">Cooperative Type *</label>
                <select name="type" required
                    class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                    <option value="">Select type...</option>
                    <option value="primary" @selected(old('type') === 'primary')>Primary</option>
                    <option value="secondary" @selected(old('type') === 'secondary')>Secondary</option>
                    <option value="other" @selected(old('type') === 'other')>Other</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Province *</label>
                    <input type="text" name="province" required value="{{ old('province') }}"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">City / Municipality *</label>
                    <input type="text" name="city" required value="{{ old('city') }}"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Barangay</label>
                    <input type="text" name="barangay" value="{{ old('barangay') }}"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Street Address</label>
                    <input type="text" name="street_address" value="{{ old('street_address') }}"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Contact Number *</label>
                    <input type="tel" inputmode="tel" name="contact_number" required value="{{ old('contact_number') }}"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Official Email *</label>
                    <input type="email" name="official_email" required value="{{ old('official_email') }}"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Year Established</label>
                    <input type="number" name="year_established" min="1900" max="{{ now()->year }}" value="{{ old('year_established') }}"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
            </div>

            <div class="form-group">
                <label class="text-xs font-bold text-slate-700 block mb-1.5">Business Activities</label>
                <textarea name="business_activities" rows="2" placeholder="e.g. Agricultural crop procurement and distribution"
                    class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">{{ old('business_activities') }}</textarea>
            </div>
        </div>

        {{-- SECTION 2: LEGAL / REGISTRATION INFORMATION --}}
        <div class="rounded-2xl border border-[#16283C]/10 bg-[#EEF0EB]/30 p-5 space-y-4">
            <p class="text-xs font-bold text-[#16283C] uppercase tracking-widest">2 · Legal / Registration Information</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">CDA Registration Number *</label>
                    <input type="text" name="cda_registration_number" required value="{{ old('cda_registration_number') }}"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Registration Date</label>
                    <input type="date" name="registration_date" value="{{ old('registration_date') }}"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Certificate of Registration *</label>
                    <input type="file" name="cert_document" accept=".pdf,.jpg,.jpeg,.png" required
                        class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-[#16283C] file:text-white file:text-xs file:font-bold">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Articles of Cooperation</label>
                    <input type="file" name="articles_document" accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-[#16283C] file:text-white file:text-xs file:font-bold">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">By-Laws</label>
                    <input type="file" name="bylaws_document" accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-[#16283C] file:text-white file:text-xs file:font-bold">
                </div>
            </div>
        </div>

        {{-- SECTION 3: AUTHORIZED REPRESENTATIVE --}}
        <div class="rounded-2xl border border-[#16283C]/10 bg-[#EEF0EB]/30 p-5 space-y-4">
            <p class="text-xs font-bold text-[#16283C] uppercase tracking-widest">3 · Authorized Representative</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Full Name *</label>
                    <input type="text" name="rep_name" required value="{{ old('rep_name') }}"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Position / Designation *</label>
                    <input type="text" name="rep_position" required value="{{ old('rep_position') }}" placeholder="e.g. Cooperative Manager"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Contact Number *</label>
                    <input type="tel" inputmode="tel" name="rep_contact" required value="{{ old('rep_contact') }}"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Email *</label>
                    <input type="email" name="rep_email" required value="{{ old('rep_email') }}"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Government-Issued ID Type *</label>
                    <input type="text" name="rep_id_type" required value="{{ old('rep_id_type') }}" placeholder="e.g. Driver's License, Passport"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">ID Number *</label>
                    <input type="text" name="rep_id_number" required value="{{ old('rep_id_number') }}"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Government ID *</label>
                    <input type="file" name="rep_id_document" accept=".pdf,.jpg,.jpeg,.png" required
                        class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-[#16283C] file:text-white file:text-xs file:font-bold">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Board Resolution / Authorization Letter *</label>
                    <input type="file" name="rep_authorization_document" accept=".pdf,.jpg,.jpeg,.png" required
                        class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-[#16283C] file:text-white file:text-xs file:font-bold">
                </div>
            </div>
        </div>

        {{-- LOGIN DETAILS --}}
        <div class="rounded-2xl border border-[#16283C]/10 bg-[#EEF0EB]/30 p-5 space-y-4">
            <p class="text-xs font-bold text-[#16283C] uppercase tracking-widest">4 · Account Login</p>
            <p class="text-[11px] text-slate-500">This is how you and our Super Admin will log in and manage this cooperative.</p>

            <div class="form-group">
                <label class="text-xs font-bold text-slate-700 block mb-1.5">Login Email *</label>
                <input type="email" name="email" required value="{{ old('email') }}" autocomplete="email"
                    class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Password *</label>
                    <input type="password" name="password" required autocomplete="new-password" minlength="8"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
                <div class="form-group">
                    <label class="text-xs font-bold text-slate-700 block mb-1.5">Confirm Password *</label>
                    <input type="password" name="password_confirmation" required autocomplete="new-password"
                        class="px-4 py-3 w-full bg-white/80 border border-[#16283C]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/10 focus:border-[#16283C] transition">
                </div>
            </div>
        </div>

        <div class="form-group pt-1">
            <label class="flex items-start gap-3 cursor-pointer p-3 rounded-xl bg-[#EEF0EB]/40 border border-[#16283C]/10">
                <input type="checkbox" name="accepted_terms" value="1" {{ old('accepted_terms') ? 'checked' : '' }}
                    class="mt-0.5 w-4 h-4 rounded border-slate-300 text-[#16283C] focus:ring-[#16283C] cursor-pointer shrink-0">
                <span class="text-xs text-slate-500 leading-relaxed">
                    I confirm this cooperative authorizes the representative above, and I agree to the
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
                Submit Cooperative Application
            </x-button>
        </div>

        <div class="mt-6 pt-5 border-t border-slate-100/80 text-center text-xs font-semibold text-slate-500">
            Already have an account?
            <a href="{{ route('login') }}" class="text-[#16283C] hover:text-[#16283C]/80 transition ml-1 hover:underline">
                Log in here
            </a>
        </div>
        <div class="mt-3 text-center">
            <a href="/" class="text-slate-400 hover:text-[#16283C] text-xs font-bold flex items-center justify-center gap-1">
                ← Return to Homepage
            </a>
        </div>
    </form>

</x-register-layout>