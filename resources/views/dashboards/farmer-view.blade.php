<x-layout>
<div class="w-full">
    <header class="pt-8 mb-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome, {{ Auth::user()->name }}</h1>
        <p class="text-gray-500 text-lg">Manage your harvests, resource pooling, and logistics from your private portal.</p>
    </header>

    {{-- PENDING VERIFICATION BANNER --}}
    @if (!Auth::user()->farmerProfile?->is_verified)
        <div class="mb-6 bg-amber-50 border border-amber-300 rounded-xl px-5 py-4 flex gap-3 items-start">
            <span class="text-xl mt-0.5">⏳</span>
            <div>
                <p class="text-sm font-semibold text-amber-800">Account Pending Verification</p>
                <p class="text-sm text-amber-700 mt-0.5">
                    Your farmer account is awaiting approval from an administrator.
                    You will be able to post harvest listings once verified.
                </p>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4 text-sm font-medium">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    <div class="report-grid">
        {{-- Active Listings --}}
        <div class="report-widget">
            <span class="text-4xl mb-3 block">🚜</span>
            <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Active Listings</h3>
            <p class="text-3xl font-black text-gray-900 mt-2">{{ $activeCount }} <span class="text-sm font-medium text-gray-400">Items</span></p>
            <div class="mt-6 pt-4 border-t border-gray-100">
                <a href="{{ route('harvests.index') }}" class="text-[#2D8A37] font-bold text-sm hover:underline">Manage posts →</a>
            </div>
        </div>
        {{-- Track Shipments --}}
        <div class="report-widget">
            <span class="text-4xl mb-3 block">📦</span>
            <h3 class="font-bold text-gray-400 text-sm uppercase tracking-wider">Track Shipments</h3>
            <p class="text-3xl font-black text-gray-900 mt-2">0 <span class="text-sm font-medium text-gray-400">Active</span></p>
            <div class="mt-6 pt-4 border-t border-gray-100">
                <a href="#" class="text-[#2D8A37] font-bold text-sm hover:underline">View map →</a>
            </div>
        </div>
        {{-- Shared Logistics --}}
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

            @if (Auth::user()->farmerProfile?->is_verified)
                <a href="{{ route('harvests.create') }}"
                    class="bg-[#2D8A37] text-white px-8 py-4 rounded-xl font-semibold hover:bg-opacity-90 transition shadow-md inline-block">
                    Post New Harvest
                </a>
                <button class="bg-slate-800 text-white px-8 py-4 rounded-xl font-semibold hover:bg-black transition shadow-md">
                    Request Pooling
                </button>
            @else
                <div class="flex items-center gap-3 bg-gray-100 border border-gray-200 rounded-xl px-6 py-4">
                    <span class="text-gray-400 text-sm font-semibold">Post New Harvest</span>
                    <span class="text-xs bg-amber-100 text-amber-700 border border-amber-200 font-bold px-2 py-0.5 rounded-full">
                        Pending Approval
                    </span>
                </div>
                <div class="flex items-center gap-3 bg-gray-100 border border-gray-200 rounded-xl px-6 py-4">
                    <span class="text-gray-400 text-sm font-semibold">Request Pooling</span>
                    <span class="text-xs bg-amber-100 text-amber-700 border border-amber-200 font-bold px-2 py-0.5 rounded-full">
                        Pending Approval
                    </span>
                </div>
            @endif

        </div>
    </div>
</div>
</x-layout>
