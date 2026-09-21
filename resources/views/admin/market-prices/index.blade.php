<x-layout title="Market Price Monitoring — HarvestHaul">
    <x-page-header title="Market Price Monitoring" :showDate="true" />

    <p class="text-sm text-slate-500 dark:text-slate-400 mb-8 max-w-2xl">
        Reference prices (e.g. DA RFO12 daily bulletins) cooperatives can compare their buying/selling prices against. Each entry is a dated snapshot — record a new one whenever fresh bulletin data comes in, past entries stay for history.
    </p>

    <x-card class="mb-6">
        <x-section-label title="Record a Price" width="w-16" />
        <form method="POST" action="{{ route('admin.market-prices.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="crop_id" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Crop <span class="text-[var(--color-error-text)]">*</span></label>
                    <select name="crop_id" id="crop_id" required class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white">
                        <option value="">Select crop…</option>
                        @foreach($crops as $crop)
                            <option value="{{ $crop->id }}">{{ $crop->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="crop_variety_id" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Variety (optional)</label>
                    <select name="crop_variety_id" id="crop_variety_id" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white">
                        <option value="">All varieties</option>
                        @foreach($varieties as $variety)
                            <option value="{{ $variety->id }}">{{ $variety->crop?->name }} ({{ $variety->name }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <x-input name="low_price_per_kg" type="number" step="0.01" label="Low (₱/kg)" required />
                <x-input name="high_price_per_kg" type="number" step="0.01" label="High (₱/kg)" required />
                <x-input name="common_price_per_kg" type="number" step="0.01" label="Common (₱/kg)" required />
                <x-input name="dpi_price_per_kg" type="number" step="0.01" label="DPI (₱/kg, optional)" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-input name="price_date" type="date" label="Price Date" required :value="now()->toDateString()" />
                <x-input name="source" label="Source" :value="'DA RFO12'" />
            </div>
            <div class="flex justify-end">
                <x-button variant="primary" size="sm">Record Price</x-button>
            </div>
        </form>
    </x-card>

    <x-card class="mb-6">
        <x-section-label title="Latest Prices" width="w-16" />
        @if($latest->isEmpty())
            <x-empty-state type="first-use" title="No prices recorded yet" description="Record the first bulletin above." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Commodity</th>
                            <th class="px-4 py-3 text-right">Low</th>
                            <th class="px-4 py-3 text-right">High</th>
                            <th class="px-4 py-3 text-right">Common</th>
                            <th class="px-4 py-3 text-right">DPI</th>
                            <th class="px-4 py-3">As Of</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($latest as $price)
                            <tr>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $price->crop?->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">
                                    {{ $price->crop?->name }}@if($price->cropVariety) ({{ $price->cropVariety->name }}) @endif
                                </td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format($price->low_price_per_kg, 2) }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ number_format($price->high_price_per_kg, 2) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-800 dark:text-slate-100">{{ number_format($price->common_price_per_kg, 2) }}</td>
                                <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-300">{{ $price->dpi_price_per_kg !== null ? number_format($price->dpi_price_per_kg, 2) : '—' }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $price->price_date->format('M d, Y') }} · {{ $price->source }}</td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('admin.market-prices.destroy', $price) }}" onsubmit="return confirm('Remove this price entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs font-bold text-[var(--color-error-text)] hover:underline">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card>
        <x-section-label title="History" width="w-16" />
        @if($history->isEmpty())
            <x-empty-state type="first-use" title="No history yet" />
        @else
            <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                @foreach($history as $price)
                    <li class="py-2.5 flex items-center justify-between gap-3 text-sm">
                        <span class="text-slate-700 dark:text-slate-300">
                            {{ $price->crop?->name }}@if($price->cropVariety) ({{ $price->cropVariety->name }}) @endif
                            — Low ₱{{ number_format($price->low_price_per_kg, 2) }} / High ₱{{ number_format($price->high_price_per_kg, 2) }} / Common ₱{{ number_format($price->common_price_per_kg, 2) }}
                        </span>
                        <span class="text-xs text-slate-400 shrink-0">{{ $price->price_date->format('M d, Y') }} · {{ $price->source }} · {{ $price->recorder?->name ?? '—' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-layout>
