<x-layout title="Outbound Order #{{ $outboundOrder->id }}">

    <div class="w-full max-w-4xl mx-auto pb-12">

        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <a href="{{ route('coop.outbound.index') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mb-4 inline-block font-semibold transition">
                ← Back to Outbound Orders
            </a>
            <span class="text-xs font-bold uppercase tracking-wider text-harvest-dark dark:text-harvest-light bg-harvest/10 dark:bg-harvest/20 px-3 py-1.5 rounded-md border border-harvest/10 dark:border-harvest/20 inline-block mb-2">Outbound Distribution</span>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Outbound Order #{{ $outboundOrder->id }}</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manifest for {{ $outboundOrder->customerCard->name }}</p>
                </div>
                @php
                    $statusBadge = match ($outboundOrder->status) {
                        'drafted'               => ['bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]', 'Drafted'],
                        'confirmed'             => ['bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-950/30 dark:text-blue-400', 'Confirmed'],
                        'in_transit'            => ['bg-orange-50 text-orange-600 border-orange-200 dark:bg-orange-950/30 dark:text-orange-400', 'In Transit'],
                        'awaiting_confirmation' => ['bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]', 'Awaiting Confirmation'],
                        'completed'             => ['bg-[var(--color-success-bg)] text-[var(--color-success-text)] border-[var(--color-success-border)]', 'Completed'],
                        'cancelled'             => ['bg-[var(--color-error-bg)] text-[var(--color-error-text)] border-[var(--color-error-border)]', 'Cancelled'],
                        default                 => ['bg-slate-50 dark:bg-slate-700 text-slate-500 dark:text-slate-400 border-slate-200/50 dark:border-slate-600', ucfirst($outboundOrder->status)],
                    };
                @endphp
                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md border {{ $statusBadge[0] }} self-start">
                    {{ $statusBadge[1] }}
                </span>
            </div>
        </header>

        <x-flash-success />
        <x-flash-error />

        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-6 mb-6">
            <h2 class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-4">Customer</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Name</p>
                    <p class="font-bold text-slate-800 dark:text-slate-200">{{ $outboundOrder->customerCard->name }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Contact</p>
                    <p class="text-slate-600 dark:text-slate-300">{{ $outboundOrder->customerCard->contact ?? '—' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Address</p>
                    <p class="text-slate-600 dark:text-slate-300">{{ $outboundOrder->customerCard->address ?? '—' }}</p>
                </div>
                @if($outboundOrder->notes)
                    <div class="sm:col-span-2">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Notes</p>
                        <p class="text-slate-600 dark:text-slate-300">{{ $outboundOrder->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700/60">
                <h2 class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Manifest</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40">
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Crop</th>
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">Kg</th>
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">Rate / Kg</th>
                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                        @foreach($outboundOrder->orderLines as $line)
                            <tr>
                                <td class="px-6 py-3 font-bold text-slate-800 dark:text-slate-200">{{ $line->crop_type }}</td>
                                <td class="px-6 py-3 text-slate-500 dark:text-slate-400 font-medium text-right">{{ number_format((float) $line->quantity_kg, 1) }}</td>
                                <td class="px-6 py-3 text-slate-500 dark:text-slate-400 font-medium text-right">₱{{ number_format((float) $line->rate_per_kg, 2) }}</td>
                                <td class="px-6 py-3 text-slate-700 dark:text-slate-300 font-bold text-right">₱{{ number_format((float) $line->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-slate-50/70 dark:bg-slate-900/40">
                            <td class="px-6 py-3 font-black text-slate-800 dark:text-white text-base" colspan="2">Total</td>
                            <td class="px-6 py-3 font-black text-slate-800 dark:text-white text-base text-right">{{ number_format((float) $outboundOrder->total_kg, 1) }} kg</td>
                            <td class="px-6 py-3 font-black text-slate-800 dark:text-white text-base text-right">₱{{ number_format((float) $outboundOrder->total_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @if($outboundOrder->status === 'drafted')
            <div class="bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200/70 dark:border-blue-900/50 rounded-2xl p-6 mb-6">
                <h2 class="text-xs font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wider mb-2">Not dispatched yet</h2>
                <p class="text-xs text-blue-700/80 dark:text-blue-300/80 font-medium">
                    Choose a truck and driver and press Dispatch to send this shipment. Once dispatched, a tracking link is created for the customer.
                </p>
            </div>
            <form action="{{ route('coop.outbound.cancel', $outboundOrder) }}" method="POST" class="inline" id="cancel-order-form-{{ $outboundOrder->id }}">
                @csrf
                <button type="button" onclick="swalConfirm(document.getElementById('cancel-order-form-{{ $outboundOrder->id }}'), {title: 'Cancel Order?', text: 'Cancel outbound order #{{ $outboundOrder->id }}? This cannot be undone.', confirmText: 'Yes, cancel', icon: 'warning', confirmColor: '#ef4444'})"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-950/20 dark:hover:bg-red-950/40 dark:text-red-400 rounded-xl text-xs font-bold transition cursor-pointer">
                    Cancel Order
                </button>
            </form>
        @elseif($outboundOrder->status === 'cancelled')
            <div class="bg-red-50/60 dark:bg-red-950/20 border border-red-200/70 dark:border-red-900/50 rounded-2xl p-6">
                <p class="text-xs font-bold text-red-700 dark:text-red-300 uppercase tracking-wider">This order was cancelled and will not be dispatched.</p>
            </div>
        @endif

    </div>

</x-layout>