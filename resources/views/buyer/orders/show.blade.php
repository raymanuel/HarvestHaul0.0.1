<x-layout title="Order {{ $buyerOrder->reference }} — HarvestHaul">
    <x-page-header title="Order {{ $buyerOrder->reference }}" :showDate="true">
        @if($buyerOrder->cooperative?->coopAdminUser)
            <x-button tag="a" variant="secondary" size="sm" href="{{ route('messages.show', $buyerOrder->cooperative->coopAdminUser) }}?context_type=buyer_order&context_id={{ $buyerOrder->id }}">Message Cooperative</x-button>
        @endif
    </x-page-header>

    <div class="max-w-2xl space-y-6">
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <x-section-label title="Order Details" width="w-20" />
                <x-badge :status="$buyerOrder->status" dot />
            </div>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Cooperative</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $buyerOrder->cooperative?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Total</dt><dd class="mt-0.5 font-bold text-slate-800 dark:text-slate-200">₱{{ number_format($buyerOrder->total_amount, 2) }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Quantity</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ number_format($buyerOrder->total_kg, 2) }} kg</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Preferred Delivery</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $buyerOrder->preferred_delivery_date?->format('M d, Y') ?? 'No preference' }}</dd></div>
                <div class="col-span-2"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Delivery Location</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $buyerOrder->delivery_address }}</dd></div>
            </dl>
            @if($buyerOrder->notes)
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Notes</dt>
                    <dd class="text-sm text-slate-600 dark:text-slate-300">{{ $buyerOrder->notes }}</dd>
                </div>
            @endif
            @if($buyerOrder->status === 'rejected' && $buyerOrder->rejection_reason)
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                    <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Rejection Reason</dt>
                    <dd class="text-sm text-slate-600 dark:text-slate-300">{{ $buyerOrder->rejection_reason }}</dd>
                </div>
            @endif
        </x-card>

        <x-card>
            <x-section-label title="Items" width="w-16" />
            <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                @foreach($buyerOrder->items as $item)
                    <li class="py-3 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $item->crop?->name ?? '—' }}@if($item->cropGrade) · {{ $item->cropGrade->name }}@endif</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ number_format($item->quantity_kg, 2) }} kg × ₱{{ number_format($item->rate_per_kg, 2) }}/kg</p>
                        </div>
                        <span class="text-sm font-extrabold text-brand-700 dark:text-gold-light">₱{{ number_format($item->subtotal, 2) }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>
</x-layout>
