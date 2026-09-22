<x-guest-layout>
    <div class="text-center mb-10 max-w-xl mx-auto">
        <a href="/" class="flex justify-center items-center gap-2 mb-4 group">
            <div class="w-8 h-8 rounded-md bg-white border border-slate-200 flex items-center justify-center shadow group-hover:scale-105 transition duration-300">
                <x-brand-logo class="w-5 h-5 text-[var(--color-brand-green)]" />
            </div>
            <span class="text-xl font-bold tracking-tight text-brand-700 dark:text-brand-light heading-font">HarvestHaul</span>
        </a>

        <h1 class="text-2xl font-bold tracking-tight text-slate-800 heading-font">Join the Distribution Network</h1>
        <p class="text-xs text-slate-500 mt-2 font-semibold">Farmers register here too, then request to join their cooperative once signed in. Delivery staff are enrolled by their cooperative.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-5xl mx-auto">

        <a href="{{ route('register.role', 'farmer') }}"
           class="flex flex-col items-center p-6 bg-white border border-[#16283C]/10 hover:border-[#16283C]/30 rounded-2xl shadow-sm no-underline text-center group transition-colors duration-300">
            <div class="w-14 h-14 rounded-2xl bg-[#EEF0EB] flex items-center justify-center text-[#16283C] mb-6 group-hover:bg-[#16283C] group-hover:text-white transition-colors duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a4 4 0 00-4-4H4v4a4 4 0 004 4h4z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a4 4 0 014-4h4v4a4 4 0 01-4 4h-4z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14v7" />
                </svg>
            </div>
            <span class="text-lg font-bold text-slate-800 heading-font mb-2">Register as a Farmer</span>
            <p class="text-[11px] text-slate-400 leading-relaxed mb-6">
                Create your account, then request to join your cooperative and start filing pickup requests.
            </p>
            <div class="w-full mt-auto py-2 bg-[#EEF0EB] text-[#16283C] text-xs font-bold rounded-xl group-hover:bg-[#16283C] group-hover:text-white transition duration-300">
                Register as Farmer →
            </div>
        </a>

        <a href="{{ route('register.role', 'cooperative') }}"
           class="flex flex-col items-center p-6 bg-white border border-[#16283C]/10 hover:border-[#16283C]/30 rounded-2xl shadow-sm no-underline text-center group transition-colors duration-300">
            <div class="w-14 h-14 rounded-2xl bg-[#EEF0EB] flex items-center justify-center text-[#16283C] mb-6 group-hover:bg-[#16283C] group-hover:text-white transition-colors duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M3 7v14h18V7l-5-4-4 4-4-4-5 4z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 21v-8h8v8" />
                </svg>
            </div>
            <span class="text-lg font-bold text-slate-800 heading-font mb-2">Register a Cooperative</span>
            <p class="text-[11px] text-slate-400 leading-relaxed mb-6">
                Submit your cooperative's registration documents. Our Super Admin reviews and verifies your account before operations begin.
            </p>
            <div class="w-full mt-auto py-2 bg-[#EEF0EB] text-[#16283C] text-xs font-bold rounded-xl group-hover:bg-[#16283C] group-hover:text-white transition duration-300">
                Register Cooperative →
            </div>
        </a>

        <a href="{{ route('register.role', 'buyer') }}"
           class="flex flex-col items-center p-6 bg-white border border-[#16283C]/10 hover:border-[#0E1620]/30 rounded-2xl shadow-sm no-underline text-center group transition-colors duration-300">
            <div class="w-14 h-14 rounded-2xl bg-[#EEF0EB] flex items-center justify-center text-[#0E1620] mb-6 group-hover:bg-[#0E1620] group-hover:text-white transition-colors duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <span class="text-lg font-bold text-slate-800 heading-font mb-2">Register as a Buyer</span>
            <p class="text-[11px] text-slate-400 leading-relaxed mb-6">
                Create a business buyer account to browse crops offered by verified cooperatives and place B2B orders.
            </p>
            <div class="w-full mt-auto py-2 bg-[#EEF0EB] text-[#0E1620] text-xs font-bold rounded-xl group-hover:bg-[#0E1620] group-hover:text-white transition duration-300">
                Register as Buyer →
            </div>
        </a>

    </div>

    <div class="mt-10 pt-5 border-t border-slate-100 text-center flex flex-col sm:flex-row justify-between gap-3 text-xs max-w-3xl mx-auto">
        <span class="text-slate-500 font-semibold mx-auto sm:mx-0">
            Already have an account?
            <a href="{{ route('login') }}" class="text-[#16283C] hover:text-[#16283C]/80 font-bold hover:underline">Log in here</a>
        </span>
        <a href="/" class="text-slate-400 hover:text-slate-600 font-bold flex items-center justify-center gap-1 mx-auto sm:mx-0">
            ← Return to Homepage
        </a>
    </div>
</x-guest-layout>