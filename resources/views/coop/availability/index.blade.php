<x-layout title="Crop Availability — HarvestHaul">
    <x-page-header title="Crop Availability" :showDate="true" />

    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 max-w-2xl">
        Inventory confirmed from procurement. Set a selling price before buyers can be quoted for it. Pulling a listing hides it without deleting its history.
    </p>

    <x-card>
        <x-section-label title="Inventory" width="w-16" />

        @if($listings->isEmpty())
            <x-empty-state type="first-use" title="No inventory yet" description="Confirmed procurement will appear here as sellable inventory." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Crop</th>
                            <th class="px-4 py-3">Grade</th>
                            <th class="px-4 py-3">Quantity</th>
                            <th class="px-4 py-3">Remaining</th>
                            <th class="px-4 py-3">Selling Price</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($listings as $listing)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $listing->crop?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $listing->cropGrade?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($listing->quantity_kg, 2) }} kg</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($listing->remaining_kg, 2) }} kg</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('coop.availability.price', $listing) }}" class="flex items-center gap-2">
                                        @csrf
                                        <span class="text-slate-400">₱</span>
                                        <input type="number" name="selling_price_per_kg" step="0.01" min="0" value="{{ $listing->selling_price_per_kg }}" placeholder="Set price"
                                               class="w-24 border border-slate-200 dark:border-slate-600 rounded-lg px-2 py-1.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white" />
                                        <button class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">Save</button>
                                    </form>
                                    @php $marketPrice = \App\Models\MarketPrice::latestFor($listing->crop_id, $listing->crop_variety_id); @endphp
                                    @if($marketPrice)
                                        <p class="text-[11px] text-slate-400 mt-1">Market common: ₱{{ number_format($marketPrice->common_price_per_kg, 2) }}/kg · {{ $marketPrice->source }}, {{ $marketPrice->price_date->format('M d') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $badge = match($listing->status) {
                                            \App\Models\CropAvailability::STATUS_AVAILABLE => ['status' => 'active', 'label' => 'Available'],
                                            \App\Models\CropAvailability::STATUS_RESERVED => ['status' => 'ready', 'label' => 'Reserved'],
                                            \App\Models\CropAvailability::STATUS_SOLD_OUT => ['status' => 'completed', 'label' => 'Sold Out'],
                                            \App\Models\CropAvailability::STATUS_ARCHIVED => ['status' => 'archived', 'label' => 'Pulled'],
                                            default => ['status' => 'default', 'label' => ucfirst($listing->status)],
                                        };
                                    @endphp
                                    <x-badge :status="$badge['status']" :label="$badge['label']" dot />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($listing->status === \App\Models\CropAvailability::STATUS_ARCHIVED)
                                        <form method="POST" action="{{ route('coop.availability.restore', $listing) }}">
                                            @csrf
                                            <button class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">Restore</button>
                                        </form>
                                    @elseif($listing->status === \App\Models\CropAvailability::STATUS_AVAILABLE)
                                        <form method="POST" action="{{ route('coop.availability.archive', $listing) }}" onsubmit="return confirm('Pull this listing from sale?');">
                                            @csrf
                                            <button class="text-xs font-bold text-[var(--color-error-text)] hover:underline">Pull</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>
