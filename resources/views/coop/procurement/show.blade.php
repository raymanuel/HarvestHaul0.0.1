@php
    $statusBadge = match($receivingRecord->status) {
        \App\Models\ReceivingRecord::STATUS_PENDING => ['status' => 'pending', 'label' => 'Awaiting Price'],
        \App\Models\ReceivingRecord::STATUS_PRICED => ['status' => 'ready', 'label' => 'Awaiting Confirmation'],
        \App\Models\ReceivingRecord::STATUS_CONFIRMED => ['status' => 'paid', 'label' => 'Confirmed'],
        \App\Models\ReceivingRecord::STATUS_CANCELLED => ['status' => 'cancelled', 'label' => 'Cancelled'],
        default => ['status' => 'default', 'label' => ucfirst($receivingRecord->status)],
    };
@endphp

<x-layout title="Procurement Record — HarvestHaul">
    <x-page-header title="Procurement Record" :showDate="true" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <x-section-label title="Receiving Details" width="w-20" />
                    <x-badge :status="$statusBadge['status']" :label="$statusBadge['label']" dot />
                </div>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Farmer</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->farmer?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Crop</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->crop?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Grade</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->cropGrade?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Sacks</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->actual_sacks ?? '—' }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Weight</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ number_format($receivingRecord->actual_weight_kg, 2) }} kg</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Recorded</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->created_at->format('M d, Y g:i A') }}</dd></div>
                </dl>
                @if($receivingRecord->remarks)
                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Remarks</dt>
                        <dd class="text-sm text-slate-600 dark:text-slate-300">{{ $receivingRecord->remarks }}</dd>
                    </div>
                @endif
            </x-card>

            @if($receivingRecord->status === \App\Models\ReceivingRecord::STATUS_PENDING)
                <x-card>
                    <x-section-label title="Set Buying Price" width="w-20" />
                    @php $marketPrice = \App\Models\MarketPrice::latestFor($receivingRecord->crop_id, $receivingRecord->crop_variety_id); @endphp
                    @if($marketPrice)
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Market reference: Low ₱{{ number_format($marketPrice->low_price_per_kg, 2) }} / Common ₱{{ number_format($marketPrice->common_price_per_kg, 2) }} / High ₱{{ number_format($marketPrice->high_price_per_kg, 2) }} per kg ({{ $marketPrice->source }}, {{ $marketPrice->price_date->format('M d, Y') }})</p>
                    @endif
                    <form method="POST" action="{{ route('coop.procurement.price', $receivingRecord) }}" class="space-y-4">
                        @csrf
                        <x-input name="buying_price_per_kg" type="number" step="0.01" label="Buying Price (₱/kg)" required placeholder="e.g. 18.00" />
                        <div>
                            <label for="remarks" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Remarks (optional)</label>
                            <textarea name="remarks" id="remarks" rows="3" maxlength="1000" class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white"></textarea>
                        </div>
                        <div class="flex justify-end">
                            <x-button variant="primary" size="sm">Save Price</x-button>
                        </div>
                    </form>
                </x-card>
            @elseif($receivingRecord->status === \App\Models\ReceivingRecord::STATUS_PRICED)
                <x-card>
                    <x-section-label title="Confirm Procurement" width="w-24" />
                    <dl class="grid grid-cols-2 gap-4 text-sm mb-4">
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Buying Price</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">₱{{ number_format($receivingRecord->buying_price_per_kg, 2) }}/kg</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Total</dt><dd class="mt-0.5 font-bold text-slate-800 dark:text-slate-200">₱{{ number_format($receivingRecord->total_amount, 2) }}</dd></div>
                    </dl>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Confirming locks this payout and notifies the farmer. This cannot be undone.</p>
                    <form method="POST" action="{{ route('coop.procurement.confirm', $receivingRecord) }}" onsubmit="return confirm('Confirm this procurement? The farmer will be notified and the payout locked in.');">
                        @csrf
                        <div class="flex justify-end">
                            <x-button variant="primary" size="sm">Confirm & Notify Farmer</x-button>
                        </div>
                    </form>
                </x-card>
            @elseif($receivingRecord->status === \App\Models\ReceivingRecord::STATUS_CONFIRMED)
                @php
                    $paymentStatus = $receivingRecord->paymentStatus();
                    $paymentBadge = ['pending' => ['pending', 'Payment Pending'], 'partial' => ['ready', 'Partially Paid'], 'paid' => ['active', 'Paid']][$paymentStatus];
                    $balanceDue = $receivingRecord->balanceDue();
                @endphp
                <x-card>
                    <div class="flex items-center justify-between mb-4">
                        <x-section-label title="Confirmed" width="w-20" />
                        <x-badge :status="$paymentBadge[0]" :label="$paymentBadge[1]" dot />
                    </div>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Buying Price</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">₱{{ number_format($receivingRecord->buying_price_per_kg, 2) }}/kg</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Total</dt><dd class="mt-0.5 font-bold text-slate-800 dark:text-slate-200">₱{{ number_format($receivingRecord->total_amount, 2) }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Confirmed By</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->confirmer?->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Confirmed At</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->confirmed_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Balance Due</dt><dd class="mt-0.5 font-bold text-slate-800 dark:text-slate-200">₱{{ number_format($balanceDue, 2) }}</dd></div>
                    </dl>
                </x-card>

                <x-card>
                    <x-section-label title="Farmer Payments" width="w-24" />

                    @if($receivingRecord->payments->isEmpty())
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
                                    @foreach($receivingRecord->payments as $payment)
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
                        <form method="POST" action="{{ route('coop.procurement.payments.store', $receivingRecord) }}" class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
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
            @elseif($receivingRecord->status === \App\Models\ReceivingRecord::STATUS_CANCELLED)
                <x-card>
                    <x-section-label title="Cancelled" width="w-20" />
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Cancelled By</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->canceller?->name ?? '—' }}</dd></div>
                        <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Cancelled At</dt><dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $receivingRecord->cancelled_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
                    </dl>
                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Reason</dt>
                        <dd class="text-sm text-slate-600 dark:text-slate-300">{{ $receivingRecord->cancellation_reason }}</dd>
                    </div>
                </x-card>
            @endif
        </div>

        <div>
            @if(in_array($receivingRecord->status, [\App\Models\ReceivingRecord::STATUS_PENDING, \App\Models\ReceivingRecord::STATUS_PRICED], true))
                <x-card>
                    <x-section-label title="Cancel" width="w-16" />
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Void this receiving record. The farmer has not been notified yet, so nothing needs to be reversed with them.</p>
                    <x-modal triggerLabel="Cancel Record" triggerClass="w-full px-4 py-2 rounded-xl text-xs font-bold text-white bg-[var(--color-error-text)] hover:opacity-90">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Cancel This Record?</h2>
                        <form method="POST" action="{{ route('coop.procurement.cancel', $receivingRecord) }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="cancellation_reason" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">Reason <span class="text-[var(--color-error-text)]">*</span></label>
                                <textarea name="cancellation_reason" id="cancellation_reason" rows="3" maxlength="500" required class="w-full border border-slate-200 dark:border-slate-600 rounded-xl px-4 py-2.5 text-sm bg-slate-50/50 dark:bg-slate-700/50 text-slate-800 dark:text-white" placeholder="e.g. duplicate entry, wrong weight recorded"></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-1">
                                <button type="button" data-modal-close class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Back</button>
                                <button class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-[var(--color-error-text)] hover:opacity-90">Cancel Record</button>
                            </div>
                        </form>
                    </x-modal>
                </x-card>
            @endif
        </div>
    </div>
</x-layout>
