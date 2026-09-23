<x-layout title="Browse Crops — HarvestHaul">
    <x-page-header title="Browse Crops" :showDate="true" />

    @unless($hasLocation)
        <div class="mb-6 rounded-xl border border-[var(--color-warning-border)] bg-[var(--color-warning-bg)] px-4 py-3 text-sm text-[var(--color-warning-text)]">
            Set your location on your <a href="{{ route('profile.show') }}" class="font-bold underline">profile</a> to see distance and get better-ranked matches.
        </div>
    @endunless

    <x-card class="mb-6">
        <form method="GET" action="{{ route('buyer.listings.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <div>
                <label for="crop_id" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Crop</label>
                <select name="crop_id" id="crop_id" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white">
                    <option value="">Any crop</option>
                    @foreach($crops as $crop)
                        <option value="{{ $crop->id }}" @selected(($filters['crop_id'] ?? null) == $crop->id)>{{ $crop->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="crop_grade_id" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Grade</label>
                <select name="crop_grade_id" id="crop_grade_id" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white">
                    <option value="">Any grade</option>
                    @foreach($cropGrades as $grade)
                        <option value="{{ $grade->id }}" @selected(($filters['crop_grade_id'] ?? null) == $grade->id)>{{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-input name="min_kg" type="number" step="0.01" label="Min Quantity (kg)" :value="$filters['min_kg'] ?? ''" placeholder="e.g. 2000" />
            <x-input name="max_price" type="number" step="0.01" label="Max Price (₱/kg)" :value="$filters['max_price'] ?? ''" placeholder="e.g. 25" />
            <div>
                <label for="sort" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Sort</label>
                <select name="sort" id="sort" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white">
                    <option value="best_match" @selected($filters['sort'] === 'best_match')>Best match</option>
                    <option value="nearest" @selected($filters['sort'] === 'nearest')>Nearest</option>
                    <option value="cheapest" @selected($filters['sort'] === 'cheapest')>Cheapest</option>
                    <option value="newest" @selected($filters['sort'] === 'newest')>Newest</option>
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-5 flex justify-end gap-3">
                <a href="{{ route('buyer.listings.index') }}" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800">Clear</a>
                <x-button variant="primary" size="sm">Apply</x-button>
            </div>
        </form>
    </x-card>

    <x-card>
        @if($listings->isEmpty())
            <x-empty-state type="first-use" title="No listings match" description="Try widening your filters or check back later." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Crop</th>
                            <th class="px-4 py-3">Cooperative</th>
                            <th class="px-4 py-3">Available</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">Match</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($listings as $i => $listing)
                            <tr class="align-top @if($i === 0 && $filters['sort'] === 'best_match') bg-brand-50/40 dark:bg-brand-900/10 @endif">
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $listing->crop?->name ?? '—' }}</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $listing->cropGrade?->name ?? '—' }}</span>
                                    @if(!empty($listing->match_reasons))
                                        <span class="block text-xs text-slate-400 dark:text-slate-500 mt-1">{{ implode(' · ', $listing->match_reasons) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $listing->cooperative?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($listing->remaining_kg, 2) }} kg</td>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">₱{{ number_format($listing->selling_price_per_kg, 2) }}/kg</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold
                                        @if($listing->match_label === 'Best match') bg-[var(--color-success-bg)] text-[var(--color-success-text)]
                                        @elseif($listing->match_label === 'Good match') bg-[var(--color-info-bg)] text-[var(--color-info-text)]
                                        @else bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300
                                        @endif">
                                        {{ $listing->match_score }} {{ $listing->match_label }}
                                    </span>
                                </td>
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
