<x-layout title="Browse Crops — HarvestHaul">
    <x-page-header title="Browse Crops" :showDate="true" />

    <x-card>
        @if($listings->isEmpty())
            <x-empty-state type="first-use" title="No listings available" description="When cooperatives list crops for sale, they appear here." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Crop</th>
                            <th class="px-4 py-3">Grade</th>
                            <th class="px-4 py-3">Cooperative</th>
                            <th class="px-4 py-3">Available</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($listings as $listing)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $listing->crop?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $listing->cropGrade?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $listing->cooperative?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($listing->remaining_kg, 2) }} kg</td>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">₱{{ number_format($listing->selling_price_per_kg, 2) }}/kg</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('buyer.listings.show', $listing) }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $listings->links() }}</div>
        @endif
    </x-card>
</x-layout>
