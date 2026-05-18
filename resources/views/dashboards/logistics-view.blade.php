<x-layout>
    <div class="w-full pb-12">
        <header class="pt-8 mb-4">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome, {{ Auth::user()->name }}</h1>
            <p class="text-gray-500 text-lg">Manage your fleet, drivers, and logistical operations from here.</p>
        </header>

        {{-- PENDING VERIFICATION BANNER --}}
        @if (!Auth::user()->logisticsProfile?->is_verified)
            <div class="mb-6 bg-amber-50 border border-amber-300 rounded-xl px-5 py-4 flex gap-3 items-start">
                <span class="text-xl mt-0.5">⏳</span>
                <div>
                    <p class="text-sm font-semibold text-amber-800">Account Pending Verification</p>
                    <p class="text-sm text-amber-700 mt-0.5">
                        Your logistics partner account is awaiting approval from an administrator.
                        You will be able to access hauling trucks and driver menu once verified.
                    </p>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl px-5 py-4 text-sm font-medium">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4 text-sm font-medium">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        <div class="report-grid">
            <div class="report-widget">
                <span class="text-4xl mb-3 block">🚛</span>
                <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Active Trucks</h3>
                <p class="text-3xl font-black text-gray-900 mt-2">0 <span class="text-sm font-medium text-gray-400">Vehicles</span></p>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    @if (Auth::user()->logisticsProfile?->is_verified)
                        <a href="#" class="text-blue-600 font-bold text-sm hover:underline">Manage vehicles →</a>
                    @else
                        <span class="text-gray-300 font-bold text-sm">Manage vehicles →</span>
                    @endif
                </div>
            </div>

            <div class="report-widget">
                <span class="text-4xl mb-3 block">👤</span>
                <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Managed Drivers</h3>
                <p class="text-3xl font-black text-gray-900 mt-2">0 <span class="text-sm font-medium text-gray-400">Staff</span></p>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    @if (Auth::user()->logisticsProfile?->is_verified)
                        <a href="#" class="text-blue-600 font-bold text-sm hover:underline">Assign tasks →</a>
                    @else
                        <span class="text-gray-300 font-bold text-sm">Assign tasks →</span>
                    @endif
                </div>
            </div>

            <div class="report-widget">
                <span class="text-4xl mb-3 block">🌾</span>
                <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Active Haul Requests</h3>
                <p class="text-3xl font-black text-gray-900 mt-2">
                    {{ $activeHarvestCount }}
                </p>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    @if (Auth::user()->logisticsProfile?->is_verified)
                        <a href="{{ route('route.optimization') }}" class="text-blue-600 font-bold text-sm hover:underline">
                            View on map →
                        </a>
                    @else
                        <span class="text-gray-300 font-bold text-sm">View on map →</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-12">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Partner Actions</h2>
            <div class="flex flex-wrap gap-4">

                @if (Auth::user()->logisticsProfile?->is_verified)
                    <button class="bg-blue-600 text-white px-8 py-4 rounded-xl font-semibold hover:bg-blue-700 transition shadow-md">
                        Add New Vehicle
                    </button>
                    <button class="bg-slate-800 text-white px-8 py-4 rounded-xl font-semibold hover:bg-black transition shadow-md">
                        Register Driver
                    </button>
                    <button class="bg-white text-slate-700 px-8 py-4 rounded-xl font-semibold hover:bg-slate-50 transition shadow-sm border border-slate-200">
                        Optimize Route Stops
                    </button>
                @else
                    <div class="flex items-center gap-3 bg-gray-100 border border-gray-200 rounded-xl px-6 py-4">
                        <span class="text-gray-400 text-sm font-semibold">Add New Vehicle</span>
                        <span class="text-xs bg-amber-100 text-amber-700 border border-amber-200 font-bold px-2 py-0.5 rounded-full">
                            Pending Approval
                        </span>
                    </div>
                    <div class="flex items-center gap-3 bg-gray-100 border border-gray-200 rounded-xl px-6 py-4">
                        <span class="text-gray-400 text-sm font-semibold">Register Driver</span>
                        <span class="text-xs bg-amber-100 text-amber-700 border border-amber-200 font-bold px-2 py-0.5 rounded-full">
                            Pending Approval
                        </span>
                    </div>
                    <div class="flex items-center gap-3 bg-gray-100 border border-gray-200 rounded-xl px-6 py-4">
                        <span class="text-gray-400 text-sm font-semibold">Optimize Route Stops</span>
                        <span class="text-xs bg-amber-100 text-amber-700 border border-amber-200 font-bold px-2 py-0.5 rounded-full">
                            Pending Approval
                        </span>
                    </div>
                @endif

            </div>
        </div>

    </div>
</x-layout>
