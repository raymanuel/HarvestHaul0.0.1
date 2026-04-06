<x-layout>
<div class="w-full">
    <header class="pt-8 mb-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome, {{ Auth::user()->name }}</h1>
        <p class="text-gray-500 text-lg">Handle the physical movement of goods and report live status updates.</p>
    </header>

    <div class="report-grid">

        <div class="report-widget">
            <span class="text-4xl mb-3 block">📍</span>
            <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Next Waypoint</h3>
            <p class="text-xl font-black text-gray-900 mt-2 line-clamp-1">Gensan Public Market</p>
            <div class="mt-6 pt-4 border-t border-gray-100">
                <a href="#" class="text-[#2D8A37] font-bold text-sm hover:underline">Launch Multi-stop Map →</a>
            </div>
        </div>

        <div class="report-widget">
            <span class="text-4xl mb-3 block">🚚</span>
            <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Active Trip</h3>
            <p class="text-xl font-black text-[#2D8A37] mt-2 italic tracking-widest">IN TRANSIT</p>
            <div class="mt-6 pt-4 border-t border-gray-100">
                <a href="#" class="text-[#2D8A37] font-bold text-sm hover:underline">Change Trip Status →</a>
            </div>
        </div>

        <div class="report-widget">
            <span class="text-4xl mb-3 block">✅</span>
            <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Daily Summary</h3>
            <p class="text-3xl font-black text-gray-900 mt-2">0 <span class="text-sm font-medium text-gray-400">Deliveries</span></p>
            <div class="mt-6 pt-4 border-t border-gray-100">
                <a href="#" class="text-[#2D8A37] font-bold text-sm hover:underline">View Uploaded PODs →</a>
            </div>
        </div>

    </div>

    <div class="mt-12">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Driver Actions</h2>
        <div class="flex flex-wrap gap-4">
            <button class="bg-[#2D8A37] text-white px-8 py-4 rounded-xl font-semibold hover:bg-opacity-90 transition shadow-md">
                Open Waypoint Navigation
            </button>
            <button class="bg-slate-800 text-white px-8 py-4 rounded-xl font-semibold hover:bg-black transition shadow-md">
                Upload Proof of Delivery
            </button>
            <button class="bg-white text-red-600 px-8 py-4 rounded-xl font-semibold hover:bg-red-50 transition shadow-sm border border-red-200">
                Report Delay/Issue
            </button>
        </div>
    </div>
</div>
</x-layout>
