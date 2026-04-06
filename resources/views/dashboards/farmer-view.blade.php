<x-layout>
<div class="w-full">
    <header class="pt-8 mb-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome, {{ Auth::user()->name }}</h1>
        <p class="text-gray-500 text-lg">Manage your harvests, resource pooling, and logistics from your private portal.</p>
    </header>

    <div class="report-grid">

        <div class="report-widget">
            <span class="text-4xl mb-3 block">🚜</span>
            <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Active Listings</h3>
            <p class="text-3xl font-black text-gray-900 mt-2">0 <span class="text-sm font-medium text-gray-400">Items</span></p>
            <div class="mt-6 pt-4 border-t border-gray-100">
                <a href="#" class="text-[#2D8A37] font-bold text-sm hover:underline">Manage posts →</a>
            </div>
        </div>

        <div class="report-widget">
            <span class="text-4xl mb-3 block">📦</span>
            <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Track Shipments</h3>
            <p class="text-3xl font-black text-gray-900 mt-2">0 <span class="text-sm font-medium text-gray-400">Active</span></p>
            <div class="mt-6 pt-4 border-t border-gray-100">
                <a href="#" class="text-[#2D8A37] font-bold text-sm hover:underline">View map →</a>
            </div>
        </div>

        <div class="report-widget">
            <span class="text-4xl mb-3 block">🤝</span>
            <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Shared Logistics</h3>
            <p class="text-3xl font-black text-gray-900 mt-2">0 <span class="text-sm font-medium text-gray-400">Slots Nearby</span></p>
            <div class="mt-6 pt-4 border-t border-gray-100">
                <a href="#" class="text-[#2D8A37] font-bold text-sm hover:underline">Find Pooling Load →</a>
            </div>
        </div>

    </div>

    <div class="mt-12">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Farmer Actions</h2>
        <div class="flex flex-wrap gap-4">
            <button class="bg-[#2D8A37] text-white px-8 py-4 rounded-xl font-semibold hover:bg-opacity-90 transition shadow-md">
                Post New Harvest
            </button>
            <button class="bg-slate-800 text-white px-8 py-4 rounded-xl font-semibold hover:bg-black transition shadow-md">
                Request Pooling
            </button>
            <button class="bg-white text-slate-700 px-8 py-4 rounded-xl font-semibold hover:bg-slate-50 transition shadow-sm border border-slate-200">
                View Market Trends
            </button>
        </div>
    </div>
</div>
</x-layout>
