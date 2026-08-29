<x-guest-layout>
    <!-- Header Segment -->
    <div class="text-center mb-10 max-w-xl mx-auto">
        <!-- Mini Logo -->
        <a href="/" class="flex justify-center items-center gap-2 mb-4 group">
            <div class="w-8 h-8 rounded-lg bg-brand-700 flex items-center justify-center text-white shadow group-hover:scale-105 transition duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 22 16 8"/>
                    <path d="M3.47 12.53 5 11l1.53 1.53a3.5 3.5 0 0 1 0 4.94L5 19l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/>
                    <path d="M7.47 8.53 9 7l1.53 1.53a3.5 3.5 0 0 1 0 4.94L9 15l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/>
                    <path d="M11.47 4.53 13 3l1.53 1.53a3.5 3.5 0 0 1 0 4.94L13 11l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/>
                    <path d="M20 2h2v2a4 4 0 0 1-4 4h-2V6a4 4 0 0 1 4-4Z"/>
                    <path d="M11.47 17.47 13 19l-1.53 1.53a3.5 3.5 0 0 1-4.94 0L5 19l1.53-1.53a3.5 3.5 0 0 1 4.94 0Z"/>
                </svg>
            </div>
            <span class="text-xl font-bold tracking-tight text-brand-700 dark:text-brand-light heading-font">HarvestHaul</span>
        </a>

        <h1 class="text-3xl font-extrabold tracking-tight text-slate-800 heading-font">Join the Dispatch Network</h1>
        <p class="text-xs text-slate-450 mt-2 font-semibold">Select your operational workspace role to begin onboarding</p>
    </div>

    <!-- Triple Choice Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-5xl mx-auto">

        <!-- Farmer card -->
        <a href="{{ route('register.role', 'farmer') }}"
           class="flex flex-col items-center p-6 bg-white border border-[#16283C]/10 hover:border-[#16283C]/30 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 no-underline text-center group">
            <div class="w-14 h-14 rounded-2xl bg-[#EEF0EB] flex items-center justify-center text-[#16283C] mb-6 group-hover:scale-110 group-hover:bg-[#16283C] group-hover:text-white transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a4 4 0 00-4-4H4v4a4 4 0 004 4h4z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a4 4 0 014-4h4v4a4 4 0 01-4 4h-4z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14v7" />
                </svg>
            </div>
            <span class="text-lg font-bold text-slate-800 heading-font mb-2">Farmer</span>
            <p class="text-[11px] text-slate-400 leading-relaxed mb-6">
                Post seasonal harvests, pool logistics capacity with neighboring growers, and split haul costs proportionally.
            </p>
            <div class="w-full mt-auto py-2 bg-[#EEF0EB] text-[#16283C] text-xs font-bold rounded-xl group-hover:bg-[#16283C] group-hover:text-white transition duration-300">
                Register as Farmer →
            </div>
        </a>

        <!-- Logistics card -->
        <a href="{{ route('register.role', 'logistics_partner') }}"
           class="flex flex-col items-center p-6 bg-white border border-[#16283C]/10 hover:border-[#0E1620]/30 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 no-underline text-center group">
            <div class="w-14 h-14 rounded-2xl bg-[#EEF0EB] flex items-center justify-center text-[#0E1620] mb-6 group-hover:scale-110 group-hover:bg-[#0E1620] group-hover:text-white transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1" />
                </svg>
            </div>
            <span class="text-lg font-bold text-slate-800 heading-font mb-2">Logistics</span>
            <p class="text-[11px] text-slate-400 leading-relaxed">
                Organize regional pickup runs, manage fleet assets, assign drivers, and trace delivery telemetry.
            </p>
            <p class="text-[10px] text-[var(--color-warning-text)] font-semibold mt-1 mb-6">
                ⓘ Includes buyer capabilities — cooperatives with trucks choose this, not Buyer.
            </p>
            <div class="w-full mt-auto py-2 bg-[#EEF0EB] text-[#0E1620] text-xs font-bold rounded-xl group-hover:bg-[#0E1620] group-hover:text-white transition duration-300">
                Register as Coordinator →
            </div>
        </a>

        <!-- Buyer card -->
        <a href="{{ route('register.role', 'buyer') }}"
           class="flex flex-col items-center p-6 bg-white border border-[#16283C]/10 hover:border-[#16283C]/30 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 no-underline text-center group">
            <div class="w-14 h-14 rounded-2xl bg-[#EEF0EB] flex items-center justify-center text-[#16283C] mb-6 group-hover:scale-110 group-hover:bg-[#16283C] group-hover:text-white transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <span class="text-lg font-bold text-slate-800 heading-font mb-2">Commercial Buyer</span>
            <p class="text-[11px] text-slate-400 leading-relaxed">
                Browse listed farmer harvests, initiate price negotiations directly, and lock deals with custom drop-off locations.
            </p>
            <p class="text-[10px] text-[var(--color-warning-text)] font-semibold mt-1 mb-6">
                ⓘ For independent buyers without fleet. Cooperatives use Logistics instead.
            </p>
            <div class="w-full mt-auto py-2 bg-[#EEF0EB] text-[#16283C] text-xs font-bold rounded-xl group-hover:bg-[#16283C] group-hover:text-white transition duration-300">
                Register as Buyer →
            </div>
        </a>

    </div>

    <!-- Footer Segment -->
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
