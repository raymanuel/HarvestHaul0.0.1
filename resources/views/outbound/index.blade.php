<x-layout title="Outbound Orders">

    <div class="w-full max-w-6xl mx-auto pb-12">

        <header class="pt-8 mb-6 border-b border-slate-200/80 dark:border-slate-700/80 pb-5">
            <a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 mb-4 inline-block font-semibold transition">
                ← Back to Dashboard
            </a>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-harvest-dark dark:text-harvest-light bg-harvest/10 dark:bg-harvest/20 px-3 py-1.5 rounded-md border border-harvest/10 dark:border-harvest/20 inline-block mb-2">Outbound Distribution</span>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight heading-font">Outbound Orders</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Your order items — packed crops heading out to customers.</p>
                </div>
                <div>
                    <x-button tag="a" :href="route('coop.outbound.create')" size="lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        New Order
                    </x-button>
                </div>
            </div>
        </header>

        <x-flash-success />
        <x-flash-error />

        @if($orders->isEmpty())
            <div class="bg-white dark:bg-slate-800 border border-slate-200/70 dark:border-slate-700/80 rounded-2xl shadow-sm p-12 text-center">
                <p class="text-4xl mb-4 font-bold text-slate-300 dark:text-slate-600">—</p>
                <p class="text-slate-800 dark:text-slate-200 font-bold text-base mb-1 heading-font">No Outbound Orders Yet</p>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-xs max-w-sm mx-auto">
                    Build your first order — pick a customer, add crop lines, then assign a truck to dispatch.
                </p>
                <a href="{{ route('coop.outbound.create') }}" class="mt-5 inline-block text-xs font-bold text-[#16283C] dark:text-[#D7BC7A] hover:underline transition">
                    Create first order <span>→</span>
                </a>
            </div>
        @else
            <x-data-table :empty-message="'No outbound orders yet.'">
                <x-slot:header>
                    <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Order</th>
                    <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Customer</th>
                    <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Total Kg</th>
                    <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Total Amount</th>
                    <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest">Status</th>
                    <th class="px-4 py-3 text-[10px] font-extrabold text-slate-500 dark:text-slate-500 uppercase tracking-widest text-right">Actions</th>
                </x-slot:header>

                @foreach($orders as $order)
                    @php
                        $statusBadge = match ($order->status) {
                            'drafted'               => ['bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]', 'Drafted'],
                            'confirmed'             => ['bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-950/30 dark:text-blue-400', 'Confirmed'],
                            'in_transit'            => ['bg-orange-50 text-orange-600 border-orange-200 dark:bg-orange-950/30 dark:text-orange-400', 'In Transit'],
                            'awaiting_confirmation' => ['bg-[var(--color-warning-bg)] text-[var(--color-warning-text)] border-[var(--color-warning-border)]', 'Awaiting Confirmation'],
                            'completed'             => ['bg-[var(--color-success-bg)] text-[var(--color-success-text)] border-[var(--color-success-border)]', 'Completed'],
                            'cancelled'             => ['bg-[var(--color-error-bg)] text-[var(--color-error-text)] border-[var(--color-error-border)]', 'Cancelled'],
                            default                 => ['bg-slate-50 dark:bg-slate-700 text-slate-500 dark:text-slate-400 border-slate-200/50 dark:border-slate-600', ucfirst($order->status)],
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40 transition">
                        <td class="px-4 py-3 font-bold text-slate-800 dark:text-slate-200 text-sm">#{{ $order->id }}</td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ $order->customerCard->name }}</td>
                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs font-medium">{{ number_format((float) $order->total_kg, 1) }} kg</td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300 text-xs font-bold">₱{{ number_format((float) $order->total_amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md border {{ $statusBadge[0] }}">
                                {{ $statusBadge[1] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('coop.outbound.show', $order) }}" class="inline-flex items-center justify-center px-3 py-2 bg-[#16283C]/10 text-[#16283C] hover:bg-[#16283C]/15 dark:bg-[#16283C]/10 dark:hover:bg-[#16283C]/15 dark:text-[#D7BC7A] rounded-xl text-xs font-bold transition cursor-pointer">View</a>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>

            <div class="mt-4">{{ $orders->links() }}</div>
        @endif

    </div>

</x-layout>