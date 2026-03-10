<div class="text-left">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome, {{ Auth::user()->name }}</h1>
    <p class="text-gray-500 mb-10 text-lg">Manage your fleet, drivers, and logistical operations from here.</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-8 bg-white/40 rounded-2xl border border-white shadow-sm">
            <span class="text-4xl mb-3 block">🚛</span>
            <h3 class="font-bold text-gray-800">Active Fleet</h3>
            <p class="text-2xl font-black text-gray-900">0 Vehicles</p>
        </div>

        <div class="p-8 bg-white/40 rounded-2xl border border-white shadow-sm">
            <span class="text-4xl mb-3 block">👤</span>
            <h3 class="font-bold text-gray-800">Managed Drivers</h3>
            <p class="text-2xl font-black text-gray-900">0 Staff</p>
        </div>

        <div class="p-8 bg-white/40 rounded-2xl border border-white shadow-sm">
            <span class="text-4xl mb-3 block">📊</span>
            <h3 class="font-bold text-gray-800">Total Revenue</h3>
            <p class="text-2xl font-black text-gray-900">₱0.00</p>
        </div>
    </div>

    <div class="mt-12 space-y-4">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Partner Actions</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <button class="bg-blue-600 text-white p-4 rounded-xl font-semibold hover:bg-blue-700 transition shadow-sm">
                Add New Vehicle
            </button>
            <button class="bg-slate-800 text-white p-4 rounded-xl font-semibold hover:bg-black transition shadow-sm">
                Register Driver
            </button>
            <button class="bg-slate-100 text-slate-700 p-4 rounded-xl font-semibold hover:bg-slate-200 transition shadow-sm border border-slate-200">
                Optimize Route Stops
            </button>
        </div>
    </div>
</div>
