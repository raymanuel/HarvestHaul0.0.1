<x-layout>

<div class="w-full max-w-4xl mx-auto pb-12">

    <!-- Ambient glow decoration -->
    <div class="absolute top-0 right-1/4 w-96 h-96 rounded-full bg-[#3A7D44]/5 blur-[120px] pointer-events-none z-0"></div>
    <div class="absolute top-1/3 left-1/3 w-[500px] h-[500px] rounded-full bg-[#1F4D25]/5 blur-[150px] pointer-events-none z-0"></div>

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-8 pt-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#3A7D44] dark:text-[#3A7D44] bg-[#3A7D44]/10 dark:bg-[#3A7D44]/10 px-3 py-1 rounded-full border border-[#3A7D44]/20">My Profile</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">Profile Settings</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Manage your account, business credentials, and security settings.</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Profile Verification Badge -->
                    @if($profile?->is_verified)
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[#3A7D44]/10 border border-[#3A7D44]/20 text-[#3A7D44] dark:text-[#3A7D44] rounded-full text-xs font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Profile Verified
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-400 rounded-full text-xs font-bold">
                            Profile Pending
                        </span>
                    @endif

                    <!-- Email Verification Badge -->
                    @if($user->hasVerifiedEmail())
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-500/10 border border-blue-500/20 text-blue-700 dark:text-blue-400 rounded-full text-xs font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Email Verified
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-400 rounded-full text-xs font-bold">
                            Email Unverified
                        </span>
                    @endif
                </div>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-6 bg-[#3A7D44]/10 border border-[#3A7D44]/20 text-[#3A7D44] dark:text-[#3A7D44] rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#3A7D44] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session('password_success'))
            <div class="mb-6 bg-[#3A7D44]/10 border border-[#3A7D44]/20 text-[#3A7D44] dark:text-[#3A7D44] rounded-2xl p-5 text-sm font-semibold flex items-center gap-3 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#3A7D44] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('password_success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 bg-red-500/10 border border-red-500/20 text-red-700 dark:text-red-400 rounded-2xl p-5 text-sm font-semibold">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('profile_complete'))
            <div style="margin-bottom:24px; padding:16px 20px; border-radius:16px; background:linear-gradient(135deg,#EFF2E9,#f0f7f0); border:1px solid #3A7D44/20;">
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <div style="width:36px; height:36px; border-radius:10px; background:#3A7D44; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div>
                        <h3 style="font-size:14px; font-weight:800; color:#1a3a1a; margin-bottom:2px;">Welcome to HarvestHaul!</h3>
                        <p style="font-size:12px; color:#4a6a4a; line-height:1.5; font-weight:500;">Please complete your business details below to start managing fleet and coordinating shipments.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════ --}}
        {{-- PROFILE FORM --}}
        {{-- ═══════════════════════════════════════════ --}}
        <form action="{{ route('profile.update') }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            {{-- ── ACCOUNT INFORMATION ── --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-[#3A7D44]/10 border border-[#3A7D44]/15 flex items-center justify-center text-[#3A7D44] dark:text-[#3A7D44] shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Account Information</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Your login credentials and representative name.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Representative Name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] transition text-sm text-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#3A7D44]/20 focus:border-[#3A7D44] transition text-sm text-slate-800 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- ── BUSINESS DETAILS ── --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-[#1F4D25]/10 border border-[#1F4D25]/15 flex items-center justify-center text-[#1F4D25] dark:text-[#1F4D25] shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Business Details</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Company information and contact number.</p>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Company / Cooperative Name</label>
                            <input type="text" name="company_name" value="{{ old('company_name', $profile->company_name ?? '') }}" required
                                class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1F4D25]/20 focus:border-[#1F4D25] transition text-sm text-slate-800 dark:text-white">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Phone Number</label>
                            <input type="text" name="phone" value="{{ old('phone', $profile->phone ?? '') }}"
                                class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1F4D25]/20 focus:border-[#1F4D25] transition text-sm text-slate-800 dark:text-white">
                        </div>
                    </div>

                    {{-- Logistics Type Badge --}}
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Organization Type:</span>
                        @if($profile?->logistics_type === 'cooperative')
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-[#3A7D44]/10 border border-[#3A7D44]/20 text-[#3A7D44] dark:text-[#3A7D44] rounded-lg text-xs font-bold">
                                Cooperative
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-[#1F4D25]/10 border border-[#1F4D25]/20 text-[#1F4D25] dark:text-[#1F4D25] rounded-lg text-xs font-bold">
                                🏢 Private Company
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── REGULATORY CREDENTIALS ── --}}
            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/15 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Regulatory Credentials</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Business permits and registration numbers for compliance.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Business Permit Number</label>
                        <input type="text" name="business_permit_no" value="{{ old('business_permit_no', $profile->business_permit_no ?? '') }}"
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition text-sm text-slate-800 dark:text-white"
                            placeholder="e.g. BP-2026-XXXXX">
                    </div>

                    @if($profile?->logistics_type === 'cooperative')
                        <div>
                            <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">CDA Registration Number</label>
                            <input type="text" name="cda_registration_no" value="{{ old('cda_registration_no', $profile->cda_registration_no ?? '') }}"
                                class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition text-sm text-slate-800 dark:text-white"
                                placeholder="e.g. CDA-XXXXX">
                        </div>
                    @else
                        <div class="flex items-end">
                            <p class="text-xs text-slate-400 dark:text-slate-500 font-medium bg-slate-50 dark:bg-slate-700/30 p-3 rounded-lg border border-slate-200/50 dark:border-slate-600/40 w-full">
                                CDA Registration is only applicable for cooperatives.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- SAVE BUTTON --}}
            <div class="flex justify-end">
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-[#3A7D44] to-[#2E6336] hover:brightness-105 text-white font-bold rounded-xl text-sm shadow-md shadow-[#3A7D44]/15 hover:shadow-lg transition duration-200 transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>

        {{-- ═══════════════════════════════════════════ --}}
        {{-- PASSWORD CHANGE --}}
        {{-- ═══════════════════════════════════════════ --}}
        <form action="{{ route('profile.password') }}" method="POST" class="mt-8">
            @csrf
            @method('PUT')

            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-violet-500/10 border border-violet-500/15 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Change Password</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Update your login password. Requires current password.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Current Password</label>
                        <input type="password" name="current_password" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">New Password</label>
                        <input type="password" name="password" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Confirm New Password</label>
                        <input type="password" name="password_confirmation" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition text-sm">
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="px-6 py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl text-xs shadow-md shadow-violet-600/15 transition duration-200 cursor-pointer inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        Update Password
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>

</x-layout>
