<x-layout>

<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />

<div class="w-full max-w-4xl mx-auto pb-12">

    <div class="relative z-10">
        <!-- Page Header -->
        <header class="mb-8 pt-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight heading-font mt-3">Profile Settings</h1>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Profile Verification Badge -->
                    @if($profile?->is_verified)
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[#16283C]/10 dark:bg-[#16283C]/10 border border-[#16283C]/20 dark:border-[#16283C]/30 text-[#16283C] dark:text-[#D7BC7A] rounded-md text-xs font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Profile Verified
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] text-[var(--color-warning-text)] rounded-md text-xs font-bold">
                            Profile Pending
                        </span>
                    @endif

                    <!-- Email Verification Badge -->
                    @if($user->hasVerifiedEmail())
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[var(--color-info-bg)] border border-[var(--color-info-border)] text-[var(--color-info-text)] rounded-md text-xs font-bold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Email Verified
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] text-[var(--color-warning-text)] rounded-md text-xs font-bold">
                            Email Unverified
                        </span>
                    @endif
                </div>
            </div>
        </header>

        {{-- Flash Messages --}}
        <x-flash-success />

        @if(session('password_success'))
            <x-flash-success :message="session('password_success')" />
        @endif

        
        @if ($errors->any())
            <div class="mb-6 bg-[var(--color-error-bg)] border border-[var(--color-error-border)] text-[var(--color-error-text)] rounded-2xl p-5 text-sm font-semibold">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('profile_complete'))
            <div class="mb-6 p-5 rounded-2xl bg-[#16283C]/5 dark:bg-[#16283C]/15 border border-[#16283C]/20 dark:border-[#16283C]/30 text-brand-dark dark:text-brand-light">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-[10px] bg-[#16283C] flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold mb-0.5">Welcome to HarvestHaul!</h3>
                        <p class="text-xs text-[#4a6a4a] dark:text-[#6a9a6a] leading-relaxed font-medium">Please complete your farm details below to start posting harvests and connecting with logistics partners.</p>
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
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-[#16283C]/10 border border-[#16283C]/15 flex items-center justify-center text-[#16283C] dark:text-[#D7BC7A] shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Account Information</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Your login credentials and display name.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="name" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Full Name</label>
<input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C] transition text-sm text-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label for="email" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Email Address</label>
<input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C] transition text-sm text-slate-800 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- ── FARM DETAILS ── --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-[#16283C]/10 border border-[#16283C]/15 flex items-center justify-center text-[#16283C] dark:text-[#D7BC7A] shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Farm Details</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Contact number and farm location.</p>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="phone" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Phone Number</label>
<input type="tel" inputmode="tel" id="phone" name="phone" value="{{ old('phone', $profile->phone ?? '') }}"
                                class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#16283C]/20 focus:border-[#16283C] transition text-sm text-slate-800 dark:text-white">
                        </div>
                        <div>
                            <label for="farm_location_display" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Farm Location</label>
                            <input type="text" id="farm_location_display" name="farm_location" value="{{ old('farm_location', $profile->farm_location ?? '') }}" readonly
                                class="px-4 py-3 w-full bg-slate-50 dark:bg-slate-700/30 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none cursor-default text-sm text-slate-600 dark:text-slate-300 font-medium">
                        </div>
                    </div>

                    {{-- Hidden coordinate inputs --}}
                    <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $profile->latitude ?? '') }}">
                    <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', $profile->longitude ?? '') }}">

                    {{-- GPS button --}}
                    <button type="button" id="use-my-location" class="w-full flex items-center justify-center gap-2 py-2.5 bg-[#16283C]/5 hover:bg-[#16283C]/10 text-[#16283C] dark:text-[#D7BC7A] border border-[#16283C]/20 rounded-xl text-xs font-bold transition shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Use My GPS Location
                    </button>

                    {{-- Map --}}
                    <div id="farm-map" class="w-full h-[250px] rounded-xl border border-[#16283C]/15 shadow-sm overflow-hidden z-0"></div>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium text-center">
                        Drag the pin or click the map to update your farm location.
                    </p>
                </div>
            </div>

            {{-- ── COOPERATIVE AFFILIATION ── --}}
            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] flex items-center justify-center text-[var(--color-warning-text)] shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Cooperative Affiliation</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Your current membership type and cooperative assignment.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    {{-- Affiliation Type Badge --}}
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Membership Type:</span>
                        @if($profile?->affiliation_type === 'cooperative')
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-[#16283C]/10 border border-[#16283C]/20 text-[#16283C] dark:text-[#D7BC7A] rounded-md text-xs font-bold">
                                Cooperative Member
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] text-[var(--color-warning-text)] rounded-md text-xs font-bold">
                                Independent Farmer
                            </span>
                        @endif
                    </div>

                    @if($profile?->membership_status === 'pending' && $profile?->cooperative_id)
                        <div class="p-4 bg-[var(--color-warning-bg)] border border-[var(--color-warning-border)] rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[var(--color-warning-text)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <span class="text-sm font-bold text-[var(--color-warning-text)]">Membership request pending for {{ $profile->cooperative->company_name }}</span>
                            </div>
<button type="button" form="cancel-coop-form" onclick="swalConfirm(document.getElementById('cancel-coop-form'), {title: 'Cancel Request?', text: 'Your pending request will be cancelled.', confirmText: 'Yes, cancel', icon: 'warning', confirmColor: '#f59e0b'})" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] hover:opacity-80 rounded-xl text-xs font-bold transition cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    Cancel Request
                                </button>
                        </div>
                    @elseif($profile?->membership_status === 'approved' && $profile?->cooperative_id)
                        <div class="p-4 bg-[#16283C]/5 dark:bg-[#16283C]/10 border border-[#16283C]/15 dark:border-[#16283C]/25 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#16283C] dark:text-[#D7BC7A] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span class="text-sm font-bold text-[#16283C] dark:text-[#D7BC7A]">Member of {{ $profile->cooperative->company_name }}</span>
                            </div>
                            <button type="button" form="leave-coop-form" onclick="swalConfirm(document.getElementById('leave-coop-form'), {title: 'Leave Cooperative?', text: 'You will no longer be a member of {{ addslashes($profile->cooperative->company_name) }}. Your harvests become independent again.', confirmText: 'Yes, leave', icon: 'warning', confirmColor: '#ef4444'})" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-950/20 dark:hover:bg-red-950/40 dark:text-red-400 rounded-xl text-xs font-bold transition cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    Leave Cooperative
                                </button>
                        </div>
                    @elseif($profile?->membership_status === 'rejected')
                        <div class="p-4 bg-slate-50 dark:bg-slate-700/30 border border-slate-200/50 dark:border-slate-600/40 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">Request was not approved.</span>
                            <a href="{{ route('farmer.join-cooperative.index') }}" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-[#16283C]/10 text-[#16283C] hover:bg-[#16283C]/15 dark:bg-[#16283C]/10 dark:hover:bg-[#16283C]/15 dark:text-[#D7BC7A] rounded-xl text-xs font-bold transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                Request to Join a Cooperative
                            </a>
                        </div>
                    @elseif($profile?->affiliation_type === 'independent' && is_null($profile?->membership_status))
                        <div class="p-4 bg-slate-50 dark:bg-slate-700/30 border border-slate-200/50 dark:border-slate-600/40 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <span class="text-sm font-bold text-slate-600 dark:text-slate-300">Join a cooperative to access shared logistics and better rates.</span>
                            <a href="{{ route('farmer.join-cooperative.index') }}" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-[#16283C]/10 text-[#16283C] hover:bg-[#16283C]/15 dark:bg-[#16283C]/10 dark:hover:bg-[#16283C]/15 dark:text-[#D7BC7A] rounded-xl text-xs font-bold transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                Join a Cooperative
                            </a>
                        </div>
                    @else
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium bg-slate-50 dark:bg-slate-700/30 p-3 rounded-md border border-slate-200/50 dark:border-slate-600/40">
                            You are registered as an independent farmer. Contact your cooperative administrator to change your affiliation type.
                        </p>
                    @endif
                </div>
            </div>

            {{-- CONFIRM PASSWORD --}}
            <x-current-password-field />

            {{-- SAVE BUTTON --}}
            <div class="flex justify-end">
                <x-button type="submit" size="lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Changes
                </x-button>
            </div>
        </form>

        {{-- Standalone forms for coop actions; buttons link via form="..." attribute (kept outside the profile form to avoid invalid HTML nesting) --}}
        @if($profile?->membership_status === 'pending' && $profile?->cooperative_id)
            <form action="{{ route('farmer.join-cooperative.cancel', $profile->cooperative_id) }}" method="POST" id="cancel-coop-form" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
        @if($profile?->membership_status === 'approved' && $profile?->cooperative_id)
            <form method="POST" action="{{ route('farmer.join-cooperative.leave') }}" id="leave-coop-form" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif

        {{-- ═══════════════════════════════════════════ --}}
        {{-- PASSWORD CHANGE --}}
        {{-- ═══════════════════════════════════════════ --}}
        <form action="{{ route('profile.password') }}" method="POST" class="mt-8">
            @csrf
            @method('PUT')

            <div class="bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700/60 rounded-2xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-brand/10 border border-brand/15 flex items-center justify-center text-brand dark:text-brand-light shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Change Password</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Update your login password. Requires current password.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label for="current_password" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Current Password</label>
                        <div class="relative">
                        <input type="password" id="current_password" name="current_password" required
                            class="px-4 py-3 pr-12 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition text-sm">
                        <button type="button" onclick="togglePassword('current_password', 'current_password-eye')" aria-label="Toggle password visibility"
                            class="absolute right-1 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center text-slate-400 hover:text-[#16283C] dark:hover:text-white transition focus:outline-none">
                            <svg id="current_password-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                        </div>
                    </div>
                    <div>
                        <label for="password" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">New Password</label>
                        <div class="relative">
                        <input type="password" id="password" name="password" required
                            class="px-4 py-3 pr-12 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition text-sm">
                        <button type="button" onclick="togglePassword('password', 'password-eye')" aria-label="Toggle password visibility"
                            class="absolute right-1 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center text-slate-400 hover:text-[#16283C] dark:hover:text-white transition focus:outline-none">
                            <svg id="password-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                        </div>
                    </div>
                    <div>
                        <label for="password_confirmation" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Confirm New Password</label>
                        <div class="relative">
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                            class="px-4 py-3 pr-12 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition text-sm">
                        <button type="button" onclick="togglePassword('password_confirmation', 'password_confirmation-eye')" aria-label="Toggle password visibility"
                            class="absolute right-1 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center text-slate-400 hover:text-[#16283C] dark:hover:text-white transition focus:outline-none">
                            <svg id="password_confirmation-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="px-6 py-2.5 bg-brand hover:bg-brand-dark text-white dark:bg-[#D7BC7A] dark:hover:bg-[#BFA05A] dark:text-[#17202B] font-bold rounded-xl text-xs transition cursor-pointer inline-flex items-center gap-2">
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

{{-- ═══════════════════════════════════════════ --}}
{{-- LEAFLET MAP SCRIPT --}}
{{-- ═══════════════════════════════════════════ --}}
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const GENSAN = [6.1164, 125.1716];
        const existingLat = {{ $profile->latitude ?? 'null' }};
        const existingLng = {{ $profile->longitude ?? 'null' }};
        const initPos = (existingLat && existingLng) ? [existingLat, existingLng] : GENSAN;
        const initZoom = (existingLat && existingLng) ? 15 : 13;

        const map = L.map('farm-map', { zoomControl: true }).setView(initPos, initZoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: ' OpenStreetMap contributors' }).addTo(map);

        const greenIcon = L.divIcon({
            html: `<div style="
                width: 18px; height: 18px; border-radius: 50%;
                background: #16283C; border: 3px solid white;
                box-shadow: 0 3px 8px rgba(45, 106, 47, 0.4);
            "></div>`,
            className: '',
            iconAnchor: [9, 9],
        });

        const marker = L.marker(initPos, { draggable: true, icon: greenIcon }).addTo(map);

        function reverseGeocode(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.display_name) {
                        const addr = data.address;
                        const parts = [
                            addr.village || addr.suburb || addr.neighbourhood || addr.hamlet,
                            addr.city || addr.town || addr.municipality,
                            addr.province || addr.state,
                        ].filter(Boolean);
                        document.getElementById('farm_location_display').value =
                            parts.length ? parts.join(', ') : data.display_name;
                    }
                })
                .catch(() => {});
        }

        function updateCoords(latlng) {
            document.getElementById('latitude').value  = latlng.lat.toFixed(8);
            document.getElementById('longitude').value = latlng.lng.toFixed(8);
            reverseGeocode(latlng.lat, latlng.lng);
        }

        marker.on('dragend', function (e) { updateCoords(e.target.getLatLng()); });
        map.on('click', function (e) { marker.setLatLng(e.latlng); updateCoords(e.latlng); });

        document.getElementById('use-my-location').addEventListener('click', function () {
            if (!navigator.geolocation) { Swal.fire({ icon: 'error', title: 'Geolocation not supported', text: 'Your browser does not support location services. Pin your location manually.', confirmButtonColor: '#16283C', background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff', color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b', customClass: { popup: 'rounded-xl' } }); return; }
            const btn = this;
            btn.textContent = 'Locating...';
            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    const latlng = L.latLng(pos.coords.latitude, pos.coords.longitude);
                    marker.setLatLng(latlng);
                    map.setView(latlng, 16);
                    updateCoords(latlng);
                    btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Use My GPS Location`;
                },
                function () {
                    Swal.fire({ icon: 'error', title: 'Could not get location', text: 'Unable to retrieve your location. Pin it manually on the map.', confirmButtonColor: '#16283C', background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#fff', color: document.documentElement.classList.contains('dark') ? '#e2e8f0' : '#1e293b', customClass: { popup: 'rounded-xl' } });
                    btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Use My GPS Location`;
                }
            );
        });

        // Fix Leaflet map rendering in hidden containers
        setTimeout(() => map.invalidateSize(), 200);
    });
</script>

</x-layout>
