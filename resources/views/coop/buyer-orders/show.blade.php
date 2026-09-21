<x-layout title="Order {{ $buyerOrder->reference }} — HarvestHaul">
    <x-page-header title="Order {{ $buyerOrder->reference }}" :showDate="true" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <x-section-label title="Order Details" width="w-20" />
                    <x-badge :status="$buyerOrder->status" dot />
                </div>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Buyer</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $buyerOrder->buyer?->name ?? '—' }}</dd></div>
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

        <div>
            @if(in_array($buyerOrder->status, ['submitted', 'under_review'], true))
                <x-card>
                    <x-section-label title="Decision" width="w-20" />
                    <form method="POST" action="{{ route('coop.buyer-orders.accept', $buyerOrder) }}" onsubmit="return confirm('Accept this order? Stock will be reserved.');" class="mb-3">
                        @csrf
                        <x-button variant="primary" size="sm" full>Accept Order</x-button>
                    </form>

                    <x-modal triggerLabel="Reject Order" triggerClass="w-full px-4 py-2 rounded-xl text-xs font-bold text-white bg-[var(--color-error-text)] hover:opacity-90">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Reject Order {{ $buyerOrder->reference }}</h2>
                        <form method="POST" action="{{ route('coop.buyer-orders.reject', $buyerOrder) }}" class="space-y-4">
                            @csrf
                            <textarea name="rejection_reason" rows="3" required maxlength="2000" placeholder="Reason for rejection" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white"></textarea>
                            <div class="flex justify-end gap-3">
                                <button type="button" data-modal-close class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Cancel</button>
                                <button class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-[var(--color-error-text)] hover:opacity-90">Reject</button>
                            </div>
                        </form>
                    </x-modal>
                </x-card>
            @elseif($buyerOrder->status === 'rejected' && $buyerOrder->rejection_reason)
                <x-card>
                    <x-section-label title="Rejection Reason" width="w-24" />
                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ $buyerOrder->rejection_reason }}</p>
                </x-card>
            @endif
        </div>
    </div>
</x-layout>
