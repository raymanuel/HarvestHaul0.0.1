<x-layout title="My Orders — HarvestHaul">
    <x-page-header title="My Orders" :showDate="true" />

    <x-card>
        @if($orders->isEmpty())
            <x-empty-state type="first-use" title="No orders yet" description="Orders you place with cooperatives appear here." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Cooperative</th>
                            <th class="px-4 py-3">Quantity</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($orders as $order)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">{{ $order->reference }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $order->cooperative?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($order->total_kg, 2) }} kg</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">₱{{ number_format($order->total_amount, 2) }}</td>
                                <td class="px-4 py-3"><x-badge :status="$order->status" dot /></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('buyer.orders.show', $order) }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $orders->links() }}</div>
        @endif
    </x-card>
</x-layout>
