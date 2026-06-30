<x-guest-layout>
    <!-- Header Segment -->
    <div class="text-center mb-10 max-w-xl mx-auto">
        <!-- Mini Logo -->
        <a href="/" class="flex justify-center items-center gap-2 mb-4 group">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#2D6A2F] to-[#5A8A3C] flex items-center justify-center text-white shadow group-hover:scale-105 transition duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                </svg>
            </div>
            <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-[#2D6A2F] to-[#5A8A3C] bg-clip-text text-transparent heading-font">HarvestHaul</span>
        </a>

        <h1 class="text-3xl font-extrabold tracking-tight text-slate-800 heading-font">Join the Dispatch Network</h1>
        <p class="text-xs text-slate-450 mt-2 font-semibold">Select your operational workspace role to begin onboarding</p>
    </div>

    <!-- Triple Choice Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-5xl mx-auto">

        <!-- Farmer card -->
        <a href="{{ route('register.role', 'farmer') }}"
           class="flex flex-col items-center p-6 bg-white border border-[#2D6A2F]/10 hover:border-[#2D6A2F]/30 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 no-underline text-center group">
            <div class="w-14 h-14 rounded-2xl bg-[#EFF2E9] flex items-center justify-center text-[#2D6A2F] mb-6 group-hover:scale-110 group-hover:bg-[#2D6A2F] group-hover:text-white transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a4 4 0 00-4-4H4v4a4 4 0 004 4h4z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a4 4 0 014-4h4v4a4 4 0 01-4 4h-4z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14v7" />
                </svg>
            </div>
            <span class="text-lg font-bold text-slate-800 heading-font mb-2">Farmer Cooperative</span>
            <p class="text-[11px] text-slate-400 leading-relaxed mb-6">
                Post seasonal harvests, pool logistics capacity with neighboring growers, and split haul costs proportionally.
            </p>
            <div class="w-full mt-auto py-2 bg-[#EFF2E9] text-[#2D6A2F] text-xs font-bold rounded-xl group-hover:bg-[#2D6A2F] group-hover:text-white transition duration-300">
                Register as Farmer →
            </div>
        </a>

        <!-- Logistics card -->
        <a href="{{ route('register.role', 'logistics_partner') }}"
           class="flex flex-col items-center p-6 bg-white border border-[#2D6A2F]/10 hover:border-[#5A8A3C]/30 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 no-underline text-center group">
            <div class="w-14 h-14 rounded-2xl bg-[#EFF2E9] flex items-center justify-center text-[#5A8A3C] mb-6 group-hover:scale-110 group-hover:bg-[#5A8A3C] group-hover:text-white transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1" />
                </svg>
            </div>
            <span class="text-lg font-bold text-slate-800 heading-font mb-2">Logistics Coordinator</span>
            <p class="text-[11px] text-slate-400 leading-relaxed mb-6">
                Organize regional pickup runs, manage fleet assets, assign drivers, and trace delivery telemetry.
            </p>
            <div class="w-full mt-auto py-2 bg-[#EFF2E9] text-[#5A8A3C] text-xs font-bold rounded-xl group-hover:bg-[#5A8A3C] group-hover:text-white transition duration-300">
                Register as Coordinator →
            </div>
        </a>

        <!-- Buyer card -->
        <a href="{{ route('register.role', 'buyer') }}"
           class="flex flex-col items-center p-6 bg-white border border-[#2D6A2F]/10 hover:border-[#1E3A8A]/30 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 no-underline text-center group">
            <div class="w-14 h-14 rounded-2xl bg-[#EFF2E9] flex items-center justify-center text-[#1E3A8A] mb-6 group-hover:scale-110 group-hover:bg-[#1E3A8A] group-hover:text-white transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <span class="text-lg font-bold text-slate-800 heading-font mb-2">Commercial Buyer</span>
            <p class="text-[11px] text-slate-400 leading-relaxed mb-6">
                Browse listed farmer harvests, initiate price negotiations directly, and lock deals with custom drop-off locations.
            </p>
            <div class="w-full mt-auto py-2 bg-[#EFF2E9] text-[#1E3A8A] text-xs font-bold rounded-xl group-hover:bg-[#1E3A8A] group-hover:text-white transition duration-300">
                Register as Buyer →
            </div>
        </a>

    </div>

    <!-- Footer Segment -->
    <div class="mt-10 pt-5 border-t border-slate-100 text-center flex flex-col sm:flex-row justify-between gap-3 text-xs max-w-3xl mx-auto">
        <span class="text-slate-500 font-semibold mx-auto sm:mx-0">
            Already have an account?
            <a href="{{ route('login') }}" class="text-[#2D6A2F] hover:text-[#2D6A2F]/80 font-bold hover:underline">Log in here</a>
        </span>
        <a href="/" class="text-slate-400 hover:text-slate-600 font-bold flex items-center justify-center gap-1 mx-auto sm:mx-0">
            ← Return to Homepage
        </a>
    </div>
</x-guest-layout>
