<div class="text-left">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome, {{ Auth::user()->name }}</h1>
    <p class="text-gray-500 mb-10 text-lg">Manage your harvests, resource pooling, and logistics from your private portal.</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-8 bg-white/40 rounded-2xl border border-white shadow-sm">
            <span class="text-4xl mb-3 block">🚜</span>
            <h3 class="font-bold text-gray-800">Active Listings</h3>
            <p class="text-2xl font-black text-gray-900">0 Items</p>
        </div>

        <div class="p-8 bg-white/40 rounded-2xl border border-white shadow-sm">
            <span class="text-4xl mb-3 block">📦</span>
            <h3 class="font-bold text-gray-800">Track Shipments</h3>
            <p class="text-2xl font-black text-gray-900">0 Active</p>
        </div>

        <div class="p-8 bg-white/40 rounded-2xl border border-white shadow-sm">
            <span class="text-4xl mb-3 block">💰</span>
            <h3 class="font-bold text-gray-800">Pooling Savings</h3>
            <p class="text-2xl font-black text-gray-900">₱0.00</p>
        </div>
    </div>

    <div class="mt-12 space-y-4">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Farmer Actions</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <button class="bg-[#2D8A37] text-white p-4 rounded-xl font-semibold hover:bg-opacity-90 transition shadow-sm">
                Post New Harvest
            </button>
            <button class="bg-slate-800 text-white p-4 rounded-xl font-semibold hover:bg-black transition shadow-sm">
                Request Pooling
            </button>
            <button class="bg-slate-100 text-slate-700 p-4 rounded-xl font-semibold hover:bg-slate-200 transition shadow-sm border border-slate-200">
                View Market Trends
            </button>
        </div>
    </div>
</div>
