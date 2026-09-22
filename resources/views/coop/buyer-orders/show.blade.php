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

            @if(! in_array($buyerOrder->status, ['submitted', 'under_review', 'rejected', 'cancelled'], true))
                @php
                    $paymentStatus = $buyerOrder->paymentStatus();
                    $paymentBadge = ['pending' => ['pending', 'Payment Pending'], 'partial' => ['ready', 'Partially Paid'], 'paid' => ['active', 'Paid']][$paymentStatus];
                    $balanceDue = $buyerOrder->balanceDue();
                @endphp
                <x-card class="mt-6">
                    <div class="flex items-center justify-between mb-4">
                        <x-section-label title="Buyer Payment" width="w-20" />
                        <x-badge :status="$paymentBadge[0]" :label="$paymentBadge[1]" dot />
                    </div>

                    @if($buyerOrder->payments->isEmpty())
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">No payments recorded yet.</p>
                    @else
                        <div class="overflow-x-auto mb-4">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        <th class="px-4 py-2">Date</th>
                                        <th class="px-4 py-2">Method</th>
                                        <th class="px-4 py-2">Reference</th>
                                        <th class="px-4 py-2">Amount</th>
                                        <th class="px-4 py-2">Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach($buyerOrder->payments as $payment)
                                        <tr>
                                            <td class="px-4 py-2 text-slate-600 dark:text-slate-300">{{ $payment->paid_at?->format('M d, Y g:i A') }}</td>
                                            <td class="px-4 py-2 text-slate-600 dark:text-slate-300">{{ ucwords(str_replace('_', ' ', $payment->method)) }}</td>
                                            <td class="px-4 py-2 text-slate-600 dark:text-slate-300">{{ $payment->reference ?? '—' }}</td>
                                            <td class="px-4 py-2 font-semibold text-slate-800 dark:text-slate-100">₱{{ number_format($payment->amount, 2) }}</td>
                                            <td class="px-4 py-2 text-slate-600 dark:text-slate-300">{{ $payment->recorder?->name ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if($balanceDue > 0)
                        <form method="POST" action="{{ route('coop.buyer-orders.payments.store', $buyerOrder) }}" class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-input name="amount" type="number" step="0.01" label="Amount (₱)" required placeholder="up to {{ number_format($balanceDue, 2) }}" />
                                <div>
                                    <label for="method" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Method</label>
                                    <select name="method" id="method" required class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white">
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="e_wallet">E-Wallet</option>
                                    </select>
                                </div>
                            </div>
                            <x-input name="reference" label="Reference (optional)" placeholder="e.g. BT-928381" />
                            <div class="flex justify-end">
                                <x-button variant="primary" size="sm">Record Payment</x-button>
                            </div>
                        </form>
                    @endif
                </x-card>
            @endif
        </div>
    </div>
</x-layout>
