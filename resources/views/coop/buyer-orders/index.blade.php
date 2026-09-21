<x-layout title="Buyer Orders — HarvestHaul">
    <x-page-header title="Buyer Orders" :showDate="true" />

    <x-card class="mb-6">
        <x-section-label title="Awaiting Review" width="w-24" />

        @if($orders->isEmpty())
            <x-empty-state type="cleared" title="Nothing to review" description="New buyer orders appear here." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Buyer</th>
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
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $order->buyer?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ number_format($order->total_kg, 2) }} kg</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">₱{{ number_format($order->total_amount, 2) }}</td>
                                <td class="px-4 py-3"><x-badge :status="$order->status" dot /></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('coop.buyer-orders.show', $order) }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">Review</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card>
        <x-section-label title="Recently Decided" width="w-24" />

        @if($recent->isEmpty())
            <x-empty-state type="first-use" title="No decisions yet" description="Accepted or rejected orders appear here." />
        @else
            <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                @foreach($recent as $order)
                    <li class="py-3 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                <a href="{{ route('coop.buyer-orders.show', $order) }}" class="hover:underline">{{ $order->reference }}</a>
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $order->buyer?->name ?? '—' }}</p>
                        </div>
                        <x-badge :status="$order->status" dot />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-layout>
