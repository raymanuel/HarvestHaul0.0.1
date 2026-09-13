@props([])

<div>
    <label for="current_password_confirm" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Current Password <span class="text-[var(--color-error-text)]">*</span></label>
    <div class="relative">
        <input type="password" id="current_password_confirm" name="current_password" required autocomplete="off"
            placeholder="Enter your password to confirm changes"
            @if (!$errors->has('current_password')) readonly onfocus="this.removeAttribute('readonly')" @endif
            class="px-4 py-3 pr-12 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-700/25 focus:border-brand-700 transition text-sm text-slate-800 dark:text-white">
        <button type="button" onclick="togglePassword('current_password_confirm', 'current_password_confirm-eye')" aria-label="Toggle password visibility"
            class="absolute right-1 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center text-slate-400 hover:text-[#16283C] dark:hover:text-white transition focus:outline-none">
            <svg id="current_password_confirm-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </button>
    </div>
    @error('current_password')
        <p class="text-xs text-[var(--color-error-text)] mt-1.5 font-semibold">{{ $message }}</p>
    @enderror
</div>
