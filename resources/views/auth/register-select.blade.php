<x-guest-layout>
    <!-- Header Segment -->
    <div class="text-center mb-10 max-w-xl mx-auto">
        <!-- Mini Logo -->
        <a href="/" class="flex justify-center items-center gap-2 mb-4 group">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white shadow group-hover:scale-105 transition duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8a13 13 0 0 1-10 10Z"/>
                </svg>
            </div>
            <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-emerald-800 to-teal-800 bg-clip-text text-transparent heading-font">HarvestHaul</span>
        </a>

        <h1 class="text-3xl font-extrabold tracking-tight text-slate-800 heading-font">Join the Dispatch Network</h1>
        <p class="text-xs text-slate-400 mt-2 font-semibold">Select your operational workspace role to begin onboarding</p>
    </div>

    <!-- Dual Choice Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-3xl mx-auto">

        <!-- Farmer card -->
        <a href="{{ route('register.role', 'farmer') }}"
           class="flex flex-col items-center p-8 bg-white border border-slate-100 hover:border-emerald-500/20 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 no-underline text-center group">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-700 mb-6 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a4 4 0 00-4-4H4v4a4 4 0 004 4h4z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a4 4 0 014-4h4v4a4 4 0 01-4 4h-4z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14v7" />
                </svg>
            </div>
            <span class="text-xl font-bold text-slate-800 heading-font mb-2">Farmer Cooperative</span>
            <p class="text-xs text-slate-400 leading-relaxed max-w-xs mb-6">
                Post seasonal harvests, pool logistics capacity with neighboring growers, and split haul costs proportionally.
            </p>
            <div class="w-full py-2.5 bg-emerald-50 text-emerald-800 text-xs font-bold rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition duration-300">
                Register as Farmer →
            </div>
        </a>

        <!-- Logistics card -->
        <a href="{{ route('register.role', 'logistics_partner') }}"
           class="flex flex-col items-center p-8 bg-white border border-slate-100 hover:border-teal-500/20 rounded-3xl shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 no-underline text-center group">
            <div class="w-16 h-16 rounded-2xl bg-teal-50 flex items-center justify-center text-teal-700 mb-6 group-hover:scale-110 group-hover:bg-teal-600 group-hover:text-white transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1" />
                </svg>
            </div>
            <span class="text-xl font-bold text-slate-800 heading-font mb-2">Logistics Coordinator</span>
            <p class="text-xs text-slate-400 leading-relaxed max-w-xs mb-6">
                Organize regional pickup runs, manage fleet assets, assign drivers, and trace delivery telemetry.
            </p>
            <div class="w-full py-2.5 bg-teal-50 text-teal-800 text-xs font-bold rounded-xl group-hover:bg-teal-600 group-hover:text-white transition duration-300">
                Register as Coordinator →
            </div>
        </a>

    </div>

    <!-- Footer Segment -->
    <div class="mt-10 pt-5 border-t border-slate-100 text-center flex flex-col sm:flex-row justify-between gap-3 text-xs max-w-3xl mx-auto">
        <span class="text-slate-500 font-semibold mx-auto sm:mx-0">
            Already have an account?
            <a href="{{ route('login') }}" class="text-emerald-700 hover:text-emerald-800 font-bold hover:underline">Log in here</a>
        </span>
        <a href="/" class="text-slate-400 hover:text-slate-600 font-bold flex items-center justify-center gap-1 mx-auto sm:mx-0">
            ← Return to Homepage
        </a>
    </div>
</x-guest-layout>
