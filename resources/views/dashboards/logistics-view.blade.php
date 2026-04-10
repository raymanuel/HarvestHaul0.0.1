<x-layout>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <div class="w-full pb-12">
        <header class="pt-8 mb-4">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome, {{ Auth::user()->name }}</h1>
            <p class="text-gray-500 text-lg">Manage your fleet, drivers, and logistical operations from here.</p>
        </header>

        <div class="report-grid">
            <div class="report-widget">
                <span class="text-4xl mb-3 block">🚛</span>
                <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Active Fleet</h3>
                <p class="text-3xl font-black text-gray-900 mt-2">0 <span class="text-sm font-medium text-gray-400">Vehicles</span></p>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <a href="#" class="text-blue-600 font-bold text-sm hover:underline">Manage vehicles →</a>
                </div>
            </div>

            <div class="report-widget">
                <span class="text-4xl mb-3 block">👤</span>
                <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Managed Drivers</h3>
                <p class="text-3xl font-black text-gray-900 mt-2">0 <span class="text-sm font-medium text-gray-400">Staff</span></p>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <a href="#" class="text-blue-600 font-bold text-sm hover:underline">Assign tasks →</a>
                </div>
            </div>

            <div class="report-widget">
                <span class="text-4xl mb-3 block">🌾</span>
                <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Active Harvest Requests</h3>
                <p class="text-3xl font-black text-gray-900 mt-2">
                    {{ \App\Models\Harvest::where('status', 'active')->count() }}
                </p>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <a href="{{ route('route.optimization') }}" class="text-blue-600 font-bold text-sm hover:underline">
                        View on map →
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-12">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Partner Actions</h2>
            <div class="flex flex-wrap gap-4">
                <button class="bg-blue-600 text-white px-8 py-4 rounded-xl font-semibold hover:bg-blue-700 transition shadow-md">
                    Add New Vehicle
                </button>
                <button class="bg-slate-800 text-white px-8 py-4 rounded-xl font-semibold hover:bg-black transition shadow-md">
                    Register Driver
                </button>
                <button class="bg-white text-slate-700 px-8 py-4 rounded-xl font-semibold hover:bg-slate-50 transition shadow-sm border border-slate-200">
                    Optimize Route Stops
                </button>
            </div>
        </div>

    </div>


</x-layout>
