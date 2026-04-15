<x-layout>
<div class="w-full">

    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600 mb-4 inline-block">
            ← Back to Dashboard
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">My Harvest Listings</h1>
                    <p class="text-gray-500">Manage your active crop listings. Active listings are visible on the logistics map.</p>
            </div>
                <a href="{{ route('harvests.create') }}"
                class="bg-[#2D8A37] text-white px-6 py-3 rounded-xl font-semibold hover:bg-opacity-90 transition shadow-md text-sm self-start sm:self-center whitespace-nowrap">
                + Post New Harvest
            </a>
        </div>
    </header>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl px-5 py-4 text-sm font-medium">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- Listings Table --}}
    @if($harvests->isEmpty())
        <div class="bg-slate-50 border border-dashed border-slate-300 rounded-xl p-16 text-center">
            <p class="text-4xl mb-4">🌾</p>
            <p class="text-gray-500 font-medium mb-4">You have no harvest listings yet.</p>
            <a href="{{ route('harvests.create') }}"
                class="bg-[#2D8A37] text-white px-6 py-3 rounded-xl font-semibold hover:bg-opacity-90 transition shadow-md text-sm inline-block">
                Post Your First Harvest
            </a>
        </div>
    @else
        <div class="table-responsive">
            <table class="w-full text-sm text-left" style="min-width: 640px;">
                <thead class="bg-slate-50 text-gray-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Crop</th>
                        <th class="px-6 py-4">Quantity</th>
                        <th class="px-6 py-4">Notes</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Posted</th>
                        <th class="px-6 py-4">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($harvests as $harvest)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 font-semibold text-gray-800">{{ $harvest->crop_type }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ number_format($harvest->quantity_kg, 2) }} kg</td>
                        <td class="px-6 py-4 text-gray-500 max-w-xs truncate">{{ $harvest->notes ?? '—' }}</td>
                        <td class="px-6 py-4">
                            @if($harvest->status === 'active')
                                <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-full uppercase">
                                    Active
                                </span>
                            @elseif($harvest->status === 'pending')
                                <span class="bg-yellow-100 text-yellow-700 text-xs font-bold px-3 py-1 rounded-full uppercase">
                                    Pending
                                </span>
                            @elseif($harvest->status === 'completed')
                                <span class="bg-blue-100 text-blue-700 text-xs font-bold px-3 py-1 rounded-full uppercase">
                                    Completed
                                </span>
                            @elseif($harvest->status === 'cancelled')
                                <span class="bg-red-100 text-red-700 text-xs font-bold px-3 py-1 rounded-full uppercase">
                                    Cancelled
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-400 text-xs">{{ $harvest->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('harvests.destroy', $harvest->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    onclick="return confirm('Remove this listing? You will no longer appear on the logistics map for this crop.')"
                                    class="text-red-500 hover:text-red-700 font-semibold text-xs">
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
</x-layout>
