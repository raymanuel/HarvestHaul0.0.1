<x-layout title="Harvest Listings">
<div class="w-full">
    <header class="pt-8 mb-8">
        <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600 mb-4 inline-block">← Back to Dashboard</a>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Harvest Listings</h1>
        <p class="text-gray-500">All harvest listings posted across the platform.</p>
    </header>

    <div class="table-responsive">
        <table class="w-full text-sm text-left" style="min-width:700px;">
            <thead class="bg-slate-50 text-gray-500 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-6 py-4">Farmer</th>
                    <th class="px-6 py-4">Crop</th>
                    <th class="px-6 py-4">Variety</th>
                    <th class="px-6 py-4">Quantity</th>
                    <th class="px-6 py-4">Harvest Date</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Posted</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($harvests as $harvest)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4 font-semibold text-gray-800">
                        {{ $harvest->farmer->name ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                        {{ $harvest->crop->name ?? $harvest->crop_type ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                        {{ $harvest->cropVariety->name ?? $harvest->variety ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-gray-600">
                        {{ number_format($harvest->quantity_kg, 2) }} kg
                    </td>
                    <td class="px-6 py-4 text-gray-400 text-xs">
                        {{ $harvest->harvest_date ? $harvest->harvest_date->format('M d, Y') : '—' }}
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $badge = match($harvest->status) {
                                'active'    => ['bg-green-100', 'text-green-700'],
                                'pending'   => ['bg-yellow-100', 'text-yellow-700'],
                                'completed' => ['bg-blue-100', 'text-blue-700'],
                                'cancelled' => ['bg-red-100', 'text-red-700'],
                                default     => ['bg-slate-100', 'text-slate-700'],
                            };
                        @endphp
                        <span class="{{ $badge[0] }} {{ $badge[1] }} text-xs font-bold px-3 py-1 rounded-full uppercase">
                            {{ $harvest->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-400 text-xs">
                        {{ $harvest->created_at->format('M d, Y') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-gray-400 text-sm">No harvest listings found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-layout>
