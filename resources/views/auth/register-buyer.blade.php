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

        {{-- TERMS & CONDITIONS --}}
        <div class="form-group pt-1">
            <label class="flex items-start gap-3 cursor-pointer p-3 rounded-xl bg-blue-50/40 border border-[#1E3A8A]/10">
                <input type="checkbox" name="accepted_terms" value="1" {{ old('accepted_terms') ? 'checked' : '' }}
                    class="mt-0.5 w-4 h-4 rounded border-slate-300 text-[#1E3A8A] focus:ring-[#1E3A8A] cursor-pointer shrink-0">
                <span class="text-xs text-slate-500 leading-relaxed">
                    I agree to the
                    <a href="javascript:void(0)" onclick="openLegalModal('{{ route('legal.terms') }}')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#1E3A8A]/10 text-[#1E3A8A] font-semibold hover:bg-[#1E3A8A]/20 transition-all text-[11px]">Terms & Conditions <span style="font-size:10px;">↗</span></a>
                    and
                    <a href="javascript:void(0)" onclick="openLegalModal('{{ route('legal.privacy') }}')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#1E3A8A]/10 text-[#1E3A8A] font-semibold hover:bg-[#1E3A8A]/20 transition-all text-[11px]">Privacy Policy <span style="font-size:10px;">↗</span></a>.
                </span>
            </label>
            @error('accepted_terms')
                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-1">
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
        {{-- LEGAL MODAL --}}
        <div id="legal-modal-overlay" style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.6);display:none;align-items:center;justify-content:center;padding:1rem;backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);" onclick="if(event.target===this)closeLegalModal()">
            <div onclick="event.stopPropagation()" style="background:#fff;border-radius:1.5rem;max-width:640px;width:100%;max-height:80vh;overflow-y:auto;position:relative;box-shadow:0 25px 50px -12px rgba(0,0,0,0.3);scrollbar-width:thin;scrollbar-color:#d1d5db transparent;">
                <div style="position:sticky;top:0;z-index:10;background:linear-gradient(135deg,#1E3A8A,#3B82F6);border-radius:1.5rem 1.5rem 0 0;padding:1.25rem 2rem 1rem;margin:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <span style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.7);">HarvestHaul</span>
                            <div id="legal-modal-title" style="font-size:1.1rem;font-weight:700;color:#fff;font-family:'Outfit',sans-serif;margin-top:0.15rem;">Loading...</div>
                        </div>
                        <button onclick="closeLegalModal()" style="width:30px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:none;background:rgba(255,255,255,0.2);color:#fff;cursor:pointer;font-size:1.1rem;transition:all 0.15s;font-family:inherit;backdrop-filter:blur(4px);" onmouseover="this.style.background='rgba(255,255,255,0.35)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">&times;</button>
                    </div>
                </div>
                <div style="padding:1.5rem 2rem 2rem;">
                    <div id="legal-modal-body" style="font-family:'Figtree',sans-serif;font-size:0.9rem;line-height:1.7;color:#374151;text-align:justify;"></div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
        function openLegalModal(url) {
            const overlay = document.getElementById('legal-modal-overlay');
            const body = document.getElementById('legal-modal-body');
            const title = document.getElementById('legal-modal-title');
            body.innerHTML = '<div style="text-align:center;padding:3rem 1rem;"><div style="width:32px;height:32px;border:3px solid #e5e7eb;border-top-color:#1E3A8A;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 1rem;"></div><p style="color:#9ca3af;font-size:0.85rem;">Loading...</p></div>';
            title.textContent = 'HarvestHaul';
            overlay.style.display = 'flex';
            fetch(url).then(r => r.text()).then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                body.innerHTML = doc.querySelector('.container').innerHTML;
                const h1 = body.querySelector('h1');
                if (h1) title.textContent = h1.textContent;
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

        document.addEventListener('DOMContentLoaded', function () {
            // No affiliation handling needed — buyer is always independent
        });
    </script>
    <style>
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear { display:none !important; }
    </style>
    @endpush
</x-register-layout>
