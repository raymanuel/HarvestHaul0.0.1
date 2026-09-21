<x-layout title="Outbound Deliveries — HarvestHaul">
    <x-page-header title="Outbound Deliveries" :showDate="true" />

    <div class="flex justify-end mb-6">
        <x-button tag="a" href="{{ route('coop.outbound.create') }}" variant="primary" size="sm">Plan a Delivery Trip</x-button>
    </div>

    <x-card class="mb-6">
        <x-section-label title="Accepted Orders Awaiting a Trip" width="w-32" />

        @if($pendingOrders->isEmpty())
            <x-empty-state type="cleared" title="Nothing waiting" description="Accepted buyer orders without a delivery trip yet appear here." />
        @else
            <ul class="divide-y divide-slate-100 dark:divide-slate-700/60">
                @foreach($pendingOrders as $order)
                    <li class="py-3 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $order->reference }} — {{ $order->buyer?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ number_format($order->total_kg, 2) }} kg · {{ $order->preferred_delivery_date?->format('M d, Y') ?? 'No preferred date' }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <x-card>
        <x-section-label title="Delivery Trips" width="w-24" />

        @if($trips->isEmpty())
            <x-empty-state type="first-use" title="No delivery trips yet" description="Trips created from the outbound planner appear here." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/70 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-4 py-3">Trip</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Truck</th>
                            <th class="px-4 py-3">Driver</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($trips as $trip)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-100">Trip #{{ $trip->id }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $trip->pickup_date?->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $trip->truck?->plate_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $trip->deliveryPersonnel?->name ?? '—' }}</td>
                                <td class="px-4 py-3"><x-badge :status="$trip->status" dot /></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('coop.outbound.show', $trip) }}" class="text-xs font-bold text-brand-700 dark:text-gold-light hover:underline">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</x-layout>
