@props([])

<div>
    <label for="current_password_confirm" class="text-xs font-bold text-slate-600 dark:text-slate-400 block mb-1.5">Current Password <span class="text-[var(--color-error-text)]">*</span></label>
    <input type="password" id="current_password_confirm" name="current_password" required autocomplete="current-password"
        placeholder="Enter your password to confirm changes"
        class="px-4 py-3 w-full bg-white/80 dark:bg-slate-700/50 border border-slate-200/80 dark:border-slate-600/60 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-700/25 focus:border-brand-700 transition text-sm text-slate-800 dark:text-white">
    @error('current_password')
        <p class="text-xs text-[var(--color-error-text)] mt-1.5 font-semibold">{{ $message }}</p>
    @enderror
</div>
