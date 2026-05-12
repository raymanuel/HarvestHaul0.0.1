<x-register-layout>
    @if ($errors->any())
    <div class="mb-4 p-3 bg-red-500/20 border border-red-500/50 rounded-lg">
        <ul class="text-xs text-red-400 list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('register.store') }}" method="POST">
        @csrf
        <input type="hidden" name="role" value="logistics_partner">

        <div class="form-group">
            <input type="text" name="name"
                placeholder="Full Name / Representative Name"
                required value="{{ old('name') }}">
        </div>

        <div class="form-group">
            <input type="email" name="email"
                placeholder="Company Email Address"
                required value="{{ old('email') }}">
        </div>

        <div class="form-group">
            <input type="text" name="phone"
                placeholder="Contact Number"
                required value="{{ old('phone') }}">
        </div>

        <div class="form-group">
            <input type="text" name="company_name"
                placeholder="Registered Company / Cooperative Name"
                required value="{{ old('company_name') }}">
        </div>

        <div class="form-group">
            <input type="text" name="business_permit_no"
                placeholder="Business Permit No."
                required value="{{ old('business_permit_no') }}"
                style="{{ $errors->has('business_permit_no') ? 'border-color:#ef4444;' : '' }}">
            @error('business_permit_no')
                <p style="font-size:0.75rem; color:#ef4444; margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Logistics Type Selector --}}
        <div class="form-group">
            <label style="font-size:0.8rem; font-weight:600; color:#374151; display:block; margin-bottom:0.5rem;">
                What type of organization are you? <span style="color:red">*</span>
            </label>
            @error('logistics_type')
                <p style="font-size:0.75rem; color:#ef4444; margin-bottom:0.5rem;">{{ $message }}</p>
            @enderror
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                <label id="label-company"
                    style="display:flex; flex-direction:column; align-items:center; justify-content:center;
                           padding:1rem 0.75rem; border:2px solid #e5e7eb; border-radius:0.75rem;
                           cursor:pointer; transition:all 0.2s; text-align:center; gap:0.35rem;">
                    <input type="radio" name="logistics_type" value="company"
                        {{ old('logistics_type') === 'company' ? 'checked' : '' }}
                        style="display:none;" onchange="handleLogisticsType()">
                    <span style="font-size:1.75rem;">🚛</span>
                    <span style="font-size:0.875rem; font-weight:700; color:#1e40af;">Logistics Company</span>
                    <span style="font-size:0.7rem; color:#6b7280;">Commercial hauling</span>
                </label>

                <label id="label-cooperative"
                    style="display:flex; flex-direction:column; align-items:center; justify-content:center;
                           padding:1rem 0.75rem; border:2px solid #e5e7eb; border-radius:0.75rem;
                           cursor:pointer; transition:all 0.2s; text-align:center; gap:0.35rem;">
                    <input type="radio" name="logistics_type" value="cooperative"
                        {{ old('logistics_type') === 'cooperative' ? 'checked' : '' }}
                        style="display:none;" onchange="handleLogisticsType()">
                    <span style="font-size:1.75rem;">🤝</span>
                    <span style="font-size:0.875rem; font-weight:700; color:#166534;">Cooperative</span>
                    <span style="font-size:0.7rem; color:#6b7280;">Farmer cooperative</span>
                </label>
            </div>
        </div>

        {{-- CDA Registration No — only shown for cooperatives --}}
        <div id="cda-field" style="display:none;" class="form-group">
            <input type="text" name="cda_registration_no"
                placeholder="CDA Registration No. (Required for Cooperatives)"
                value="{{ old('cda_registration_no') }}"
                style="{{ $errors->has('cda_registration_no') ? 'border-color:#ef4444;' : '' }}">
            @error('cda_registration_no')
                <p style="font-size:0.75rem; color:#ef4444; margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div style="position:relative; margin-bottom:1rem;">
            <input type="password" name="password" id="password"
                placeholder="Password" required
                style="width:100%; box-sizing:border-box; padding:0.75rem 2.75rem 0.75rem 1rem;
                       border:1px solid #e5e7eb; border-radius:0.75rem; font-size:0.95rem;
                       color:#111827; outline:none;
                       {{ $errors->has('password') ? 'border-color:#ef4444;' : '' }}">
            <button type="button" onclick="togglePassword('password', 'eyeIcon1')"
                style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%);
                       background:none; border:none; cursor:pointer; color:#94a3b8; padding:0; line-height:0;">
                <svg id="eyeIcon1" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </button>
            @error('password')
                <p style="font-size:0.75rem; color:#ef4444; margin-top:0.4rem;">{{ $message }}</p>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div style="position:relative; margin-bottom:1rem;">
            <input type="password" name="password_confirmation" id="password_confirmation"
                placeholder="Confirm Password" required
                style="width:100%; box-sizing:border-box; padding:0.75rem 2.75rem 0.75rem 1rem;
                       border:1px solid #e5e7eb; border-radius:0.75rem; font-size:0.95rem;
                       color:#111827; outline:none;">
            <button type="button" onclick="togglePassword('password_confirmation', 'eyeIcon2')"
                style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%);
                       background:none; border:none; cursor:pointer; color:#94a3b8; padding:0; line-height:0;">
                <svg id="eyeIcon2" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </button>
        </div>

        <button type="submit" class="primary-btn">Register as Logistics Coordinator</button>

        <div style="margin-top:1.5rem; font-size:0.85rem; color:#6b7280;">
            Not a logistics coordinator?
            <a href="{{ route('register.role', 'farmer') }}"
                style="color:#2D8A37; font-weight:600; text-decoration:none;">
                Sign up as Farmer
            </a>
        </div>
    </form>

    @push('scripts')
    <script>
        function handleLogisticsType() {
            const company     = document.querySelector('input[name="logistics_type"][value="company"]');
            const cooperative = document.querySelector('input[name="logistics_type"][value="cooperative"]');
            const cdaField    = document.getElementById('cda-field');
            const labelCo     = document.getElementById('label-company');
            const labelCoop   = document.getElementById('label-cooperative');

            labelCo.style.borderColor   = company.checked     ? '#3b82f6' : '#e5e7eb';
            labelCo.style.background    = company.checked     ? '#eff6ff' : '';
            labelCoop.style.borderColor = cooperative.checked ? '#2D8A37' : '#e5e7eb';
            labelCoop.style.background  = cooperative.checked ? '#f0fdf4' : '';

            cdaField.style.display = cooperative.checked ? 'block' : 'none';
            const cdaInput = cdaField.querySelector('input');
            cdaInput.required = cooperative.checked;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const oldType = "{{ old('logistics_type') }}";
            if (oldType) {
                const radio = document.querySelector(`input[name="logistics_type"][value="${oldType}"]`);
                if (radio) { radio.checked = true; handleLogisticsType(); }
            }

            // If validation failed and cda error exists, keep field visible
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
