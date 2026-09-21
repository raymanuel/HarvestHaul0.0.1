@php
    $approved = (Auth::user()->buyerProfile?->status ?? null) === 'approved';
@endphp

<x-layout title="{{ $listing->crop?->name ?? 'Listing' }} — HarvestHaul">
    <x-page-header title="{{ $listing->crop?->name ?? 'Listing' }}" :showDate="true" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card>
                <x-section-label title="Listing Details" width="w-20" />
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Crop</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $listing->crop?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Grade</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $listing->cropGrade?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Cooperative</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $listing->cooperative?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Available</dt><dd class="mt-0.5 font-bold text-slate-800 dark:text-slate-200">{{ number_format($listing->remaining_kg, 2) }} kg</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Price</dt><dd class="mt-0.5 font-bold text-slate-800 dark:text-slate-200">₱{{ number_format($listing->selling_price_per_kg, 2) }}/kg</dd></div>
                </dl>
            </x-card>
        </div>

        <div>
            <x-card>
                <x-section-label title="Place Order" width="w-20" />

                @if(! $approved)
                    <p class="text-xs text-slate-500 dark:text-slate-400">Your buyer account must be approved before you can place orders.</p>
                @else
                    <form method="POST" action="{{ route('buyer.orders.store') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="crop_availability_id" value="{{ $listing->id }}">
                        <x-input name="quantity_kg" type="number" step="0.01" label="Quantity (kg)" required placeholder="up to {{ $listing->remaining_kg }}" />
                        <x-input name="preferred_delivery_date" type="date" label="Preferred Delivery Date (optional)" />
                        <div>
                            <label for="delivery_address" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Delivery Location <span class="text-[var(--color-error-text)]">*</span></label>
                            <textarea name="delivery_address" id="delivery_address" rows="2" required maxlength="1000" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white"></textarea>
                        </div>

                        <x-location-picker
                            latField="delivery_latitude"
                            lngField="delivery_longitude"
                            label="Pin Delivery Location on Map"
                        />
                        <div>
                            <label for="notes" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Notes (optional)</label>
                            <textarea name="notes" id="notes" rows="2" maxlength="1000" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white"></textarea>
                        </div>
                        <div class="flex justify-end">
                            <x-button variant="primary" size="sm">Submit Order</x-button>
                        </div>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
</x-layout>
