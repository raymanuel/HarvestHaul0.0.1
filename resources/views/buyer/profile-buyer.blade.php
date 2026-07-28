<x-layout>

<div class="w-full max-w-4xl mx-auto pb-12">

    <div class="relative z-10">
        <header class="mb-8 pt-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-brand dark:text-brand-light bg-brand/10 dark:bg-brand/10 px-3 py-1 rounded-full border border-brand/20">My Profile</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight heading-font mt-3">Profile Settings</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Manage your account details and contact information.</p>
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

        <x-flash-success />

        <x-flash-success :message="session('password_success')" />

        @if ($errors->any())
            <div class="mb-6 bg-red-500/10 border border-red-500/20 text-red-700 dark:text-red-400 rounded-2xl p-5 text-sm font-semibold">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-[#3A7D44]/10 border border-[#3A7D44]/15 flex items-center justify-center text-[#3A7D44] dark:text-[#3A7D44] shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Account Information</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Your login credentials and display name.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Full Name</label>
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

            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-[#1F4D25]/10 border border-[#1F4D25]/15 flex items-center justify-center text-[#1F4D25] dark:text-[#1F4D25] shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 dark:text-white heading-font">Contact Information</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500 font-medium">Your phone number for buyer communications.</p>
                    </div>
                </div>

                <div class="max-w-sm">
                    <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}"
                        class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#1F4D25]/20 focus:border-[#1F4D25] transition text-sm text-slate-800 dark:text-white">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-[#3A7D44] to-[#2E6336] hover:brightness-105 text-white font-bold rounded-xl text-sm shadow-md shadow-[#3A7D44]/15 hover:shadow-lg transition duration-200 transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>

        <form action="{{ route('profile.password') }}" method="POST" class="mt-8">
            @csrf
            @method('PUT')

            <div class="bg-white dark:bg-slate-800/80 backdrop-blur border border-slate-200/60 dark:border-slate-700/60 rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-brand/10 border border-brand/15 flex items-center justify-center text-brand dark:text-brand-light shrink-0">
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
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition text-sm dark:text-white">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">New Password</label>
                        <input type="password" name="password" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition text-sm dark:text-white">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Confirm New Password</label>
                        <input type="password" name="password_confirmation" required
                            class="px-4 py-3 w-full bg-white/80 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition text-sm dark:text-white">
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="px-6 py-2.5 bg-brand hover:bg-brand-dark text-white font-bold rounded-xl text-xs shadow-md shadow-brand/15 transition duration-200 cursor-pointer inline-flex items-center gap-2">
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
