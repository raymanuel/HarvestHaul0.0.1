<div class="text-left">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome, {{ Auth::user()->name }}</h1>
    <p class="text-gray-500 mb-10 text-lg">Handle the physical movement of goods and report live status updates.</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-8 bg-white/40 rounded-2xl border border-white shadow-sm">
            <span class="text-4xl mb-3 block">📍</span>
            <h3 class="font-bold text-gray-800">Next Waypoint</h3>
            <p class="text-xl font-black text-gray-900 line-clamp-1">Gensan Public Market</p>
        </div>

        <div class="p-8 bg-white/40 rounded-2xl border border-white shadow-sm">
            <span class="text-4xl mb-3 block">🚚</span>
            <h3 class="font-bold text-gray-800">Trip Status</h3>
            <p class="text-xl font-black text-[#2D8A37]">IN TRANSIT</p>
        </div>

        <div class="p-8 bg-white/40 rounded-2xl border border-white shadow-sm">
            <span class="text-4xl mb-3 block">✅</span>
            <h3 class="font-bold text-gray-800">Completed Today</h3>
            <p class="text-2xl font-black text-gray-900">0 Deliveries</p>
        </div>
    </div>

    <div class="mt-12 space-y-4">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Driver Actions</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <button class="bg-[#2D8A37] text-white p-4 rounded-xl font-semibold hover:bg-opacity-90 transition shadow-sm">
                Open Waypoint Navigation
            </button>
            <button class="bg-slate-800 text-white p-4 rounded-xl font-semibold hover:bg-black transition shadow-sm">
                Update Trip Status
            </button>
            <button class="bg-red-50 text-red-600 p-4 rounded-xl font-semibold hover:bg-red-100 transition shadow-sm border border-red-200">
                Report an Issue
            </button>
        </div>
    </div>
</div>
