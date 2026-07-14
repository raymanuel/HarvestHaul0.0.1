<x-register-layout maxWidth="480px">

    <div class="mb-8 text-center">
        <div class="w-14 h-14 bg-gradient-to-tr from-[#3A7D44] to-[#2E6336] text-white rounded-2xl flex items-center justify-center mx-auto mb-3.5 shadow-md shadow-[#3A7D44]/10">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1m-6 0a1 1 0 001-1m9 1a1 1 0 01-1-1m-3 0a1 1 0 001-1m-1 0H8m9-1v-4a1 1 0 00-1-1h-2" />
            </svg>
        </div>
        <h2 class="text-xl font-extrabold text-slate-800 heading-font tracking-tight">Logistics Partner</h2>
        <p class="text-xs text-slate-505 mt-1.5 font-semibold">Join the network, dispatch trucks, and secure cargo contracts</p>
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
                <input type="text" name="name" placeholder="Full Name / Representative Name" required value="{{ old('name') }}" autocomplete="name"
                    class="px-4 py-3 w-full bg-white/80 border border-[#3A7D44]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44] transition">
            </div>
        </div>

        {{-- EMAIL --}}
        <div class="form-group">
            <div class="relative">
                <input type="email" name="email" placeholder="Company Email Address" required value="{{ old('email') }}" autocomplete="email"
                    class="px-4 py-3 w-full bg-white/80 border border-[#3A7D44]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44] transition">
            </div>
        </div>

        {{-- PHONE --}}
        <div class="form-group">
            <div class="relative">
                <input type="text" name="phone" placeholder="Contact Number" required value="{{ old('phone') }}" autocomplete="tel"
                    class="px-4 py-3 w-full bg-white/80 border border-[#3A7D44]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44] transition">
            </div>
        </div>

        {{-- COMPANY NAME --}}
        <div class="form-group">
            <div class="relative">
                <input type="text" name="company_name" placeholder="Registered Company / Cooperative Name" required value="{{ old('company_name') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-[#3A7D44]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44] transition">
            </div>
        </div>

        {{-- BUSINESS PERMIT NO --}}
        <div class="form-group">
            <div class="relative">
                <input type="text" name="business_permit_no" placeholder="Business Permit No. (Optional)" value="{{ old('business_permit_no') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-[#3A7D44]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44] transition">
            </div>
            @error('business_permit_no')
                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
            @enderror
        </div>

        {{-- Logistics Type Selector --}}
        <div class="form-group space-y-2">
            <label class="text-xs font-bold text-slate-650 block">
                What type of organization are you? <span class="text-red-505">*</span>
            </label>
            @error('logistics_type')
                <p class="text-xs text-red-500 mt-0.5 font-semibold">{{ $message }}</p>
            @enderror
            <div class="grid grid-cols-2 gap-3.5">
                <!-- Company Card -->
                <label id="label-company" class="flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#3A7D44]/30 hover:bg-[#EFF2E9]/10">
                    <input type="radio" name="logistics_type" value="company"
                        {{ old('logistics_type') === 'company' ? 'checked' : '' }}
                        class="hidden" onchange="handleLogisticsType()">
                    <span class="text-xs font-bold text-slate-800">Logistics Company</span>
                    <span class="text-[9px] text-slate-400 font-medium leading-tight">Commercial hauler</span>
                </label>

                <!-- Cooperative Card -->
                <label id="label-cooperative" class="flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#3A7D44]/30 hover:bg-[#EFF2E9]/10">
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
                <input type="text" name="cda_registration_no" placeholder="CDA Registration No. (Optional)" value="{{ old('cda_registration_no') }}"
                    class="px-4 py-3 w-full bg-white/80 border border-[#3A7D44]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44] transition">
            </div>
            @error('cda_registration_no')
                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
            @enderror
        </div>

        {{-- PASSWORD --}}
        <div class="form-group">
            <div class="relative">
                <input type="password" name="password" id="password" placeholder="Password" required autocomplete="new-password"
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-[#3A7D44]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44] transition">
                <button type="button" onclick="togglePassword('password', 'eyeIcon1')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#3A7D44] transition focus:outline-none">
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
            <div id="pw-strength" style="display:none; margin-top:8px;">
                <div style="display:flex; gap:4px; margin-bottom:4px;">
                    <div id="pw-bar-1" style="flex:1; height:3px; border-radius:2px; background:#e5e7eb; transition:background 0.3s;"></div>
                    <div id="pw-bar-2" style="flex:1; height:3px; border-radius:2px; background:#e5e7eb; transition:background 0.3s;"></div>
                    <div id="pw-bar-3" style="flex:1; height:3px; border-radius:2px; background:#e5e7eb; transition:background 0.3s;"></div>
                    <div id="pw-bar-4" style="flex:1; height:3px; border-radius:2px; background:#e5e7eb; transition:background 0.3s;"></div>
                </div>
                <p id="pw-label" style="font-size:11px; font-weight:600; color:#9ca3af; margin:0;"></p>
            </div>
        </div>

        {{-- CONFIRM PASSWORD --}}
        <div class="form-group">
            <div class="relative">
                <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm Password" required autocomplete="new-password"
                    class="pl-4 pr-12 py-3 w-full bg-white/80 border border-[#3A7D44]/15 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/10 focus:border-[#3A7D44] transition">
                <button type="button" onclick="togglePassword('password_confirmation', 'eyeIcon2')"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#3A7D44] transition focus:outline-none">
                    <svg id="eyeIcon2" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- TERMS & CONDITIONS --}}
        <div class="form-group pt-1">
            <label class="flex items-start gap-3 cursor-pointer p-3 rounded-xl bg-[#EFF2E9]/40 border border-[#3A7D44]/10">
                <input type="checkbox" name="accepted_terms" value="1" {{ old('accepted_terms') ? 'checked' : '' }}
                    class="mt-0.5 w-4 h-4 rounded border-slate-300 text-[#3A7D44] focus:ring-[#3A7D44] cursor-pointer shrink-0">
                <span class="text-xs text-slate-500 leading-relaxed">
                    I agree to the
                    <a href="javascript:void(0)" onclick="openLegalModal('{{ route('legal.terms') }}')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#3A7D44]/10 text-[#3A7D44] font-semibold hover:bg-[#3A7D44]/20 hover:text-[#1f4d21] transition-all text-[11px]">Terms & Conditions <span style="font-size:10px;">↗</span></a>
                    and
                    <a href="javascript:void(0)" onclick="openLegalModal('{{ route('legal.privacy') }}')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#3A7D44]/10 text-[#3A7D44] font-semibold hover:bg-[#3A7D44]/20 hover:text-[#1f4d21] transition-all text-[11px]">Privacy Policy <span style="font-size:10px;">↗</span></a>.
                </span>
            </label>
            @error('accepted_terms')
                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-1">
            <button type="submit" class="w-full py-3 bg-gradient-to-r from-[#3A7D44] to-[#2E6336] hover:brightness-105 text-white font-bold rounded-xl text-sm shadow-md shadow-[#3A7D44]/10 hover:shadow-lg transition duration-200 transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer">
                Register as Logistics Coordinator
            </button>
        </div>

        <div class="mt-6 pt-5 border-t border-slate-100/80 text-center text-xs font-semibold text-slate-455">
            Not a logistics coordinator?
            <a href="{{ route('register.role', 'farmer') }}"
                class="text-[#3A7D44] hover:text-[#3A7D44]/80 transition ml-1 hover:underline">
                Sign up as Farmer
            </a>
        </div>
        <div class="mt-3 text-center">
            <a href="/" class="text-slate-400 hover:text-[#3A7D44] text-xs font-bold flex items-center justify-center gap-1">
                ← Return to Homepage
            </a>
        </div>
        {{-- LEGAL MODAL --}}
        <div id="legal-modal-overlay" style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);display:none;align-items:center;justify-content:center;padding:1rem;backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);" onclick="if(event.target===this)closeLegalModal()">
            <div onclick="event.stopPropagation()" style="background:#fff;border-radius:1.5rem;max-width:640px;width:100%;max-height:80vh;overflow-y:auto;position:relative;box-shadow:0 25px 50px -12px rgba(0,0,0,0.3);scrollbar-width:thin;scrollbar-color:#d1d5db transparent;">
                <div style="position:sticky;top:0;z-index:10;background:linear-gradient(135deg,#3A7D44,#2E6336);border-radius:1.5rem 1.5rem 0 0;padding:1.25rem 2rem 1rem;margin:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <span style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.7);">HarvestHaul</span>
                            <div id="legal-modal-title" style="font-size:1.1rem;font-weight:700;color:#fff;font-family:'Outfit',sans-serif;margin-top:0.15rem;">Loading...</div>
                        </div>
                        <button onclick="closeLegalModal()" style="width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:none;background:rgba(255,255,255,0.2);color:#fff;cursor:pointer;font-size:1.1rem;transition:all 0.15s;font-family:inherit;backdrop-filter:blur(4px);" onmouseover="this.style.background='rgba(255,255,255,0.35)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">&times;</button>
                    </div>
                </div>
                <div style="padding:1.5rem 2rem 2rem;">
                    <div id="legal-modal-body" style="font-family:'Source Sans 3',sans-serif;font-size:0.9rem;line-height:1.7;color:#374151;text-align:justify;"></div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        // PASSWORD STRENGTH
        function checkPasswordStrength(pw) {
            let score = 0;
            if (pw.length >= 8) score++;
            if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
            if (/\d/.test(pw)) score++;
            if (/[^a-zA-Z0-9]/.test(pw)) score++;
            return score;
        }
        function updateStrengthUI(score) {
            const container = document.getElementById('pw-strength');
            const bars = [1,2,3,4].map(i => document.getElementById('pw-bar-'+i));
            const label = document.getElementById('pw-label');
            const colors = ['#ef4444','#f59e0b','#eab308','#3A7D44'];
            const labels = ['Weak','Fair','Good','Strong'];
            const pw = document.getElementById('password').value;
            if (!pw) { container.style.display='none'; return; }
            container.style.display='block';
            bars.forEach((bar,i) => { bar.style.background = i < score ? colors[score-1] : '#e5e7eb'; });
            label.textContent = score > 0 ? labels[score-1] : 'Too short';
            label.style.color = score > 0 ? colors[score-1] : '#9ca3af';
        }
        document.getElementById('password').addEventListener('input', function() {
            updateStrengthUI(checkPasswordStrength(this.value));
        });

        const legalCache = {};
        function openLegalModal(url) {
            const overlay = document.getElementById('legal-modal-overlay');
            const body = document.getElementById('legal-modal-body');
            const title = document.getElementById('legal-modal-title');
            title.textContent = 'HarvestHaul';
            overlay.style.display = 'flex';
            if (legalCache[url]) {
                body.innerHTML = legalCache[url];
                const h1 = body.querySelector('h1');
                if (h1) title.textContent = h1.textContent;
                return;
            }
            body.innerHTML = '<div style="text-align:center;padding:3rem 1rem;"><div style="width:32px;height:32px;border:3px solid #e5e7eb;border-top-color:#3A7D44;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 1rem;"></div><p style="color:#9ca3af;font-size:0.85rem;">Loading...</p></div>';
            fetch(url).then(r => r.text()).then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                body.innerHTML = doc.querySelector('.container').innerHTML;
                const h1 = body.querySelector('h1');
                if (h1) title.textContent = h1.textContent;
                legalCache[url] = body.innerHTML;
            }).catch(() => {
                body.innerHTML = '<p style="color:#dc2626;padding:2rem;text-align:center;">Failed to load. Please try again.</p>';
            });
        }
        function closeLegalModal() {
            document.getElementById('legal-modal-overlay').style.display = 'none';
        }
        document.addEventListener('click', function(e) {
            if (e.target.id === 'legal-modal-overlay') closeLegalModal();
        });

        function handleLogisticsType() {
            const company     = document.querySelector('input[name="logistics_type"][value="company"]');
            const cooperative = document.querySelector('input[name="logistics_type"][value="cooperative"]');
            const cdaField    = document.getElementById('cda-field');
            const labelCo     = document.getElementById('label-company');
            const labelCoop   = document.getElementById('label-cooperative');

            if (company.checked) {
                labelCo.className = "flex flex-col items-center justify-center p-4 border-2 border-[#3A7D44] bg-[#EFF2E9]/30 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 scale-[1.02] shadow-sm";
            } else {
                labelCo.className = "flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#3A7D44]/30 hover:bg-[#EFF2E9]/10";
            }

            if (cooperative.checked) {
                labelCoop.className = "flex flex-col items-center justify-center p-4 border-2 border-[#3A7D44] bg-[#EFF2E9]/30 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 scale-[1.02] shadow-sm";
            } else {
                labelCoop.className = "flex flex-col items-center justify-center p-4 border-2 border-slate-200/80 rounded-2xl cursor-pointer transition-all duration-200 text-center gap-1 hover:border-[#3A7D44]/30 hover:bg-[#EFF2E9]/10";
            }

            cdaField.style.display = cooperative.checked ? 'block' : 'none';
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
